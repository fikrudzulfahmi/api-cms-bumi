<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Support\Slug;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    /** Query dasar: penulis + hitungan interaksi (dipakai daftar & detail). */
    protected function dasar()
    {
        return Post::with('author')->withCount([
            'likes as jumlah_like' => fn ($q) => $q->where('tipe', PostLike::LIKE),
            'likes as jumlah_dislike' => fn ($q) => $q->where('tipe', PostLike::DISLIKE),
            'komentar as jumlah_komentar' => fn ($q) => $q->where('is_approved', true),
        ]);
    }

    /**
     * Publik — berita/pengumuman/prestasi terpublikasi.
     * Mendukung ?kategori=, ?limit=, ?search=, ?urut=terbaru|trending.
     */
    public function index(Request $request)
    {
        $query = $this->dasar()->published();

        if ($request->filled('kategori')) {
            $query->kategori($request->kategori);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('judul', 'like', '%'.$request->search.'%')
                    ->orWhere('ringkasan', 'like', '%'.$request->search.'%');
            });
        }

        // trending = paling banyak dibaca; bawaan = terbaru.
        if ($request->input('urut') === 'trending') {
            $query->orderByDesc('views')->orderByDesc('id');
        } else {
            $query->latest('tanggal')->latest('id');
        }

        if ($request->filled('limit')) {
            return response()->json(['data' => $query->limit((int) $request->limit)->get()]);
        }

        return response()->json($query->paginate(12));
    }

    /** Publik — detail berita berdasarkan slug. */
    public function show(string $slug)
    {
        $post = $this->dasar()->published()->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => $post]);
    }

    /**
     * Admin/Penulis — daftar berita (termasuk draft) lengkap dengan statistik:
     * pengunjung, like, dislike, komentar, dan rating.
     * Penulis hanya melihat berita miliknya sendiri.
     */
    public function adminIndex(Request $request)
    {
        $query = $this->dasar()->latest('id');

        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('kategori')) {
            $query->kategori($request->kategori);
        }

        if ($request->filled('search')) {
            $query->where('judul', 'like', '%'.$request->search.'%');
        }

        if ($request->input('urut') === 'trending') {
            $query->orderByDesc('views');
        } elseif ($request->input('urut') === 'populer') {
            $query->withCount(['likes as _urut_like' => fn ($q) => $q->where('tipe', PostLike::LIKE)])
                ->orderByDesc('_urut_like');
        }

        return response()->json(['data' => $query->get()]);
    }

    /**
     * Admin — rekap analisis berita untuk dashboard:
     * total pengunjung/like/dislike/komentar, rating rata-rata, dan 5 berita terpopuler.
     */
    public function adminAnalitik(Request $request)
    {
        $total = [
            'berita' => Post::count(),
            'terbit' => Post::published()->count(),
            'pengunjung' => (int) Post::sum('views'),
            'like' => PostLike::like()->count(),
            'dislike' => PostLike::dislike()->count(),
            'komentar' => PostComment::disetujui()->count(),
            'komentar_menunggu' => PostComment::where('is_approved', false)->count(),
        ];

        $terbit = $this->dasar()->published()->get();
        $total['rating_rata'] = $terbit->isEmpty() ? 0.0 : round($terbit->avg(fn ($p) => $p->rating), 1);

        return response()->json(['data' => [
            'total' => $total,
            'terpopuler' => $this->dasar()->published()->orderByDesc('views')->limit(5)->get(),
        ]]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['slug'] = $this->resolveSlug($request);
        $data['user_id'] = $request->user()->id; // penulis otomatis

        return response()->json(['data' => Post::create($data)->load('author')], 201);
    }

    public function update(Request $request, Post $post)
    {
        if ($deny = $this->denyIfNotOwner($request, $post)) {
            return $deny;
        }

        $data = $request->validate($this->rules());
        $data['slug'] = $this->resolveSlug($request, $post);

        $post->update($data);

        return response()->json(['data' => $post->fresh()->load('author')]);
    }

    public function destroy(Request $request, Post $post)
    {
        if ($deny = $this->denyIfNotOwner($request, $post)) {
            return $deny;
        }

        $post->delete();

        return response()->json(['message' => 'Berita dihapus.']);
    }

    /** Penulis hanya boleh mengubah/menghapus berita miliknya. */
    protected function denyIfNotOwner(Request $request, Post $post)
    {
        $user = $request->user();

        if (! $user->isAdmin() && $post->user_id !== $user->id) {
            return response()->json([
                'message' => 'Anda hanya bisa mengelola berita milik Anda sendiri.',
            ], 403);
        }

        return null;
    }

    protected function rules(): array
    {
        return [
            'judul' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            // Kategori diambil dari daftar yang bisa diatur admin di panel.
            'kategori' => ['required', 'string', 'max:60', Rule::in(Post::daftarKategori())],
            'gambar' => 'nullable|string|max:500',
            'ringkasan' => 'nullable|string',
            'konten' => 'nullable|string',
            'tanggal' => 'nullable|date',
            'is_published' => 'nullable|boolean',
            // SEO ala Yoast
            'meta_judul' => 'nullable|string|max:255',
            'meta_deskripsi' => 'nullable|string|max:320',
            'kata_kunci' => 'nullable|string|max:120',
        ];
    }

    protected function resolveSlug(Request $request, ?Post $post = null): string
    {
        $source = $request->filled('slug') ? $request->slug : $request->judul;

        return Slug::unique(Post::class, $source, 'slug', $post?->id);
    }
}
