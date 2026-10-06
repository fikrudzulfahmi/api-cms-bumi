<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\PostView;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Interaksi pembaca pada berita: kunjungan, like/dislike, dan komentar.
 * Semua endpoint di sini PUBLIK (pengunjung situs tidak punya akun).
 *
 * Anti-curang sederhana tetapi efektif:
 *  - `sid` = hash(IP + user agent) → satu orang dianggap satu pengunjung.
 *  - kunjungan dihitung sekali per hari per orang (refresh tidak menambah).
 *  - like/dislike satu per orang per berita (boleh diganti, bukan ditambah).
 *  - komentar tampil hanya setelah disetujui admin + dibatasi 1 per jam.
 */
class PostInteractionController extends Controller
{
    /** Catat kunjungan. */
    public function view(Request $request, string $slug)
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        // insertOrIgnore: baris ganda (pengunjung sama di hari sama) diabaikan DB,
        // dan aman kalau ada dua permintaan datang bersamaan.
        $baru = DB::table('post_views')->insertOrIgnore([
            'post_id' => $post->id,
            'sid' => $this->sid($request),
            'tanggal' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;

        if ($baru) {
            // Query builder: tidak memicu event model, jadi log aktivitas tetap bersih.
            Post::where('id', $post->id)->increment('views');
        }

        return response()->json(['data' => [
            'views' => (int) Post::where('id', $post->id)->value('views'),
            'dihitung' => $baru,
        ]]);
    }

    /** Keadaan reaksi sekarang + pilihan pengunjung ini. */
    public function reaksi(Request $request, string $slug)
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();
        $sid = $this->sid($request);

        return response()->json(['data' => [
            'like' => $post->likes()->like()->count(),
            'dislike' => $post->likes()->dislike()->count(),
            'reaksi_saya' => PostLike::where('post_id', $post->id)->where('sid', $sid)->value('tipe'),
            'rating' => $post->fresh()->rating,
        ]]);
    }

    /** Like / dislike — memilih yang sama dua kali akan membatalkannya. */
    public function like(Request $request, string $slug)
    {
        $request->validate(['tipe' => 'required|in:like,dislike'], [
            'tipe.in' => 'Pilihan reaksi tidak dikenal.',
        ]);

        $post = Post::published()->where('slug', $slug)->firstOrFail();
        $sid = $this->sid($request);

        $ada = PostLike::where('post_id', $post->id)->where('sid', $sid)->first();

        if ($ada && $ada->tipe === $request->tipe) {
            $ada->delete();                     // klik ulang = batal
        } else {
            PostLike::updateOrCreate(
                ['post_id' => $post->id, 'sid' => $sid],
                ['tipe' => $request->tipe]
            );
        }

        $segar = $post->fresh();

        return response()->json(['data' => [
            'like' => $segar->likes()->like()->count(),
            'dislike' => $segar->likes()->dislike()->count(),
            'reaksi_saya' => PostLike::where('post_id', $post->id)->where('sid', $sid)->value('tipe'),
            'rating' => $segar->rating,
        ]]);
    }

    /**
     * Komentar yang sudah disetujui, lengkap dengan balasannya.
     * Balasan selalu dikelompokkan di bawah komentar utama (maksimal 2 tingkat).
     */
    public function komentar(string $slug)
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        $kolom = ['id', 'parent_id', 'nama', 'isi', 'balas_ke', 'user_id', 'created_at'];

        return response()->json([
            'data' => $post->komentar()
                ->disetujui()
                ->induk()
                ->with(['children' => fn ($q) => $q->disetujui()->oldest('id')->select($kolom)])
                ->latest('id')
                ->get($kolom),
        ]);
    }

    /**
     * Kirim komentar — boleh juga sebagai balasan (pengunjung saling membalas).
     * Bila membalas sebuah balasan, sasaran dipindah ke komentar utamanya dan
     * nama yang disapa dicatat di `balas_ke` ("@Nama").
     */
    public function kirimKomentar(Request $request, string $slug)
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'nama' => 'required|string|max:80',
            'email' => 'nullable|email|max:120',
            'isi' => 'required|string|min:5|max:1500',
            'parent_id' => 'nullable|integer',
            'balas_ke' => 'nullable|string|max:80',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'isi.required' => 'Komentar tidak boleh kosong.',
            'isi.min' => 'Komentar terlalu pendek (minimal 5 karakter).',
            'isi.max' => 'Komentar terlalu panjang (maksimal 1500 karakter).',
            'email.email' => 'Alamat email tidak sah.',
        ]);

        $parentId = null;
        $balasKe = trim((string) ($data['balas_ke'] ?? '')) ?: null;

        if (! empty($data['parent_id'])) {
            $sasaran = PostComment::where('post_id', $post->id)->find($data['parent_id']);

            if (! $sasaran) {
                return response()->json(['message' => 'Komentar yang ingin dibalas tidak ditemukan.'], 422);
            }

            if ($sasaran->parent_id) {
                // Membalas sebuah balasan → tetap ditempel di komentar utamanya.
                $balasKe = $balasKe ?: $sasaran->nama;
                $parentId = $sasaran->parent_id;
            } else {
                $parentId = $sasaran->id;
            }
        }

        // Batas: maksimal 5 kiriman per jam per pengunjung per berita
        // (cukup untuk bercakap-cakap, tetapi menahan spam).
        $sejam = PostComment::where('post_id', $post->id)
            ->where('ip', $request->ip())
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($sejam >= 5) {
            return response()->json([
                'message' => 'Terlalu banyak komentar dalam satu jam. Silakan coba lagi nanti.',
            ], 429);
        }

        $komentar = PostComment::create([
            'post_id' => $post->id,
            'parent_id' => $parentId,
            'balas_ke' => $balasKe,
            'nama' => $data['nama'],
            'email' => $data['email'] ?? null,
            'isi' => $data['isi'],
            'ip' => $request->ip(),
            'is_approved' => false,
        ]);

        Activity::log(
            $parentId ? 'balas_komentar' : 'komentar_baru',
            sprintf(
                '%s %s pada: %s',
                $komentar->nama,
                $parentId ? 'membalas komentar' : 'mengirim komentar baru',
                $post->judul
            ),
            [
                'severity' => 'info',
                'status' => 201,
                'subject_type' => 'Post',
                'subject_id' => (string) $post->id,
                'data' => ['komentar_id' => $komentar->id, 'induk' => $parentId],
            ]
        );

        return response()->json([
            'data' => ['id' => $komentar->id],
            'message' => $parentId
                ? 'Terima kasih! Balasan Anda akan tampil setelah disetujui pengelola.'
                : 'Terima kasih! Komentar Anda akan tampil setelah disetujui pengelola.',
        ], 201);
    }

    /** sid = hash IP + user agent (identitas pengunjung tanpa akun). */
    protected function sid(Request $request): string
    {
        return hash('sha256', ($request->ip() ?? '-').'|'.($request->userAgent() ?? '-'));
    }
}
