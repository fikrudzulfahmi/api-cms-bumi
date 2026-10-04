<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\Slug;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Publik — berita/pengumuman/prestasi terpublikasi.
     * Mendukung ?kategori=, ?limit=, ?search=.
     */
    public function index(Request $request)
    {
        $query = Post::with('author')->published()->latest('tanggal')->latest('id');

        if ($request->filled('kategori') && in_array($request->kategori, Post::KATEGORI, true)) {
            $query->kategori($request->kategori);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('judul', 'like', '%'.$request->search.'%')
                    ->orWhere('ringkasan', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('limit')) {
            return response()->json(['data' => $query->limit((int) $request->limit)->get()]);
        }

        return response()->json($query->paginate(12));
    }

    /**
     * Publik — detail berita berdasarkan slug.
     */
    public function show(string $slug)
    {
        $post = Post::with('author')->published()->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => $post]);
    }

    /**
     * Admin/Penulis — daftar berita (termasuk draft).
     * Penulis hanya melihat berita miliknya sendiri.
     */
    public function adminIndex(Request $request)
    {
        $query = Post::with('author')->latest('id');

        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('kategori') && in_array($request->kategori, Post::KATEGORI, true)) {
            $query->kategori($request->kategori);
        }

        if ($request->filled('search')) {
            $query->where('judul', 'like', '%'.$request->search.'%');
        }

        return response()->json(['data' => $query->get()]);
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
            'kategori' => 'required|in:berita,pengumuman,prestasi',
            'gambar' => 'nullable|string|max:500',
            'ringkasan' => 'nullable|string',
            'konten' => 'nullable|string',
            'tanggal' => 'nullable|date',
            'is_published' => 'nullable|boolean',
        ];
    }

    protected function resolveSlug(Request $request, ?Post $post = null): string
    {
        $source = $request->filled('slug') ? $request->slug : $request->judul;

        return Slug::unique(Post::class, $source, 'slug', $post?->id);
    }
}
