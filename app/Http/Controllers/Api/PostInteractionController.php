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

    /** Komentar yang sudah disetujui — termasuk balasan pengelola bila ada. */
    public function komentar(string $slug)
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        return response()->json([
            'data' => $post->komentar()->disetujui()->latest()->get([
                'id', 'nama', 'isi', 'created_at',
                'balasan', 'balasan_at', 'balasan_oleh',
            ]),
        ]);
    }

    /** Kirim komentar — tampil setelah disetujui pengelola. */
    public function kirimKomentar(Request $request, string $slug)
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'nama' => 'required|string|max:80',
            'email' => 'nullable|email|max:120',
            'isi' => 'required|string|min:5|max:1500',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'isi.required' => 'Komentar tidak boleh kosong.',
            'isi.min' => 'Komentar terlalu pendek (minimal 5 karakter).',
            'isi.max' => 'Komentar terlalu panjang (maksimal 1500 karakter).',
            'email.email' => 'Alamat email tidak sah.',
        ]);

        $baruSejam = PostComment::where('post_id', $post->id)
            ->where('ip', $request->ip())
            ->where('created_at', '>=', now()->subHour())
            ->exists();

        if ($baruSejam) {
            return response()->json([
                'message' => 'Anda baru saja mengirim komentar. Mohon tunggu satu jam lagi.',
            ], 429);
        }

        $komentar = PostComment::create($data + [
            'post_id' => $post->id,
            'ip' => $request->ip(),
            'is_approved' => false,
        ]);

        Activity::log('komentar_baru', "Komentar baru dari {$komentar->nama} pada: {$post->judul}", [
            'severity' => 'info',
            'status' => 201,
            'subject_type' => 'Post',
            'subject_id' => (string) $post->id,
            'data' => ['panjang' => strlen($komentar->isi)],
        ]);

        return response()->json([
            'data' => ['id' => $komentar->id],
            'message' => 'Terima kasih! Komentar Anda akan tampil setelah disetujui pengelola.',
        ], 201);
    }

    /** sid = hash IP + user agent (identitas pengunjung tanpa akun). */
    protected function sid(Request $request): string
    {
        return hash('sha256', ($request->ip() ?? '-').'|'.($request->userAgent() ?? '-'));
    }
}
