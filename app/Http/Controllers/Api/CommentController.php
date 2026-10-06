<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PostComment;
use App\Support\Activity;
use Illuminate\Http\Request;

/**
 * Moderasi komentar & balasan berita.
 *
 * Hak akses:
 *  - ADMIN   → semua komentar/balasan.
 *  - PENULIS → hanya yang berada di berita MILIKNYA SENDIRI.
 *  - Selain itu → 403.
 *
 * Yang bisa dilakukan: membalas (sebagai pengelola), mengubah isi, menyetujui/
 * menyembunyikan, dan menghapus — baik komentar pengunjung maupun balasan
 * (termasuk balasan pengunjung yang tidak pantas). Menghapus komentar utama
 * otomatis menghapus seluruh balasannya.
 */
class CommentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = PostComment::with([
            'post:id,judul,slug,user_id',
            'parent:id,nama,isi',
        ])->withCount('children')->latest('id');

        // Penulis hanya melihat komentar pada beritanya sendiri.
        if (! $user->isAdmin()) {
            $query->whereHas('post', fn ($q) => $q->where('user_id', $user->id));
        }

        if ($request->filled('status')) {
            $query->where('is_approved', $request->input('status') === 'disetujui');
        }

        if ($request->filled('jenis')) {
            $request->input('jenis') === 'balasan' ? $query->balasan() : $query->induk();
        }

        // Belum dibalas = komentar utama yang belum punya balasan sama sekali.
        if ($request->filled('dibalas')) {
            $request->input('dibalas') === 'sudah'
                ? $query->induk()->sudahDibalas()
                : $query->induk()->whereDoesntHave('children');
        }

        if ($request->filled('post_id')) {
            $query->where('post_id', (int) $request->input('post_id'));
        }

        if ($request->filled('q')) {
            $cari = $request->input('q');
            $query->where(function ($w) use ($cari) {
                $w->where('nama', 'like', "%{$cari}%")
                    ->orWhere('isi', 'like', "%{$cari}%")
                    ->orWhere('balas_ke', 'like', "%{$cari}%");
            });
        }

        return response()->json($query->paginate(min((int) $request->input('per_page', 30), 200)));
    }

    /**
     * Balas sebagai pengelola (penulis/admin).
     * Balasan resmi selalu ditempel di komentar utama dan langsung tampil.
     */
    public function balas(Request $request, PostComment $comment)
    {
        if (! $this->bolehKelola($request, $comment)) {
            return $this->tolak();
        }

        $data = $request->validate([
            'balasan' => 'required|string|min:2|max:1000',
        ], [
            'balasan.required' => 'Isi balasan tidak boleh kosong.',
            'balasan.min' => 'Balasan terlalu pendek.',
            'balasan.max' => 'Balasan terlalu panjang (maksimal 1000 karakter).',
        ]);

        $indukId = $comment->parent_id ?: $comment->id;
        $balasKe = $comment->parent_id ? $comment->nama : null;

        $balasan = PostComment::create([
            'post_id' => $comment->post_id,
            'parent_id' => $indukId,
            'user_id' => $request->user()->id,
            'balas_ke' => $balasKe,
            'nama' => $request->user()->name,
            'isi' => $data['balasan'],
            'is_approved' => true,
            'ip' => $request->ip(),
        ]);

        // Penulis menjawab = komentar itu pantas tampil.
        if (! $comment->is_approved) {
            $comment->update(['is_approved' => true]);
        }

        Activity::log(
            'balas_komentar',
            "{$request->user()->name} membalas komentar dari {$comment->nama} pada: ".($comment->post?->judul ?? '-'),
            [
                'severity' => 'info',
                'status' => 201,
                'subject_type' => 'Post',
                'subject_id' => (string) $comment->post_id,
                'data' => ['balasan_id' => $balasan->id, 'komentar_id' => $comment->id],
            ]
        );

        return response()->json([
            'data' => $balasan->fresh()->load(['post:id,judul,slug,user_id', 'parent:id,nama,isi']),
            'message' => 'Balasan terkirim dan komentar kini tampil di situs.',
        ], 201);
    }

    /** Ubah isi komentar/balasan, atau setujui/sembunyikan. */
    public function update(Request $request, PostComment $comment)
    {
        if (! $this->bolehKelola($request, $comment)) {
            return $this->tolak();
        }

        $data = $request->validate([
            'is_approved' => 'nullable|boolean',
            'isi' => 'nullable|string|min:2|max:1500',
        ], [
            'isi.min' => 'Isi terlalu pendek.',
            'isi.max' => 'Isi terlalu panjang (maksimal 1500 karakter).',
        ]);

        $ubah = [];
        if ($request->has('is_approved')) {
            $ubah['is_approved'] = $request->boolean('is_approved');
        }
        if ($request->filled('isi')) {
            $ubah['isi'] = $data['isi'];
        }

        if (! $ubah) {
            return response()->json(['message' => 'Tidak ada perubahan yang dikirim.'], 422);
        }

        $comment->update($ubah);

        $jenis = $comment->parent_id ? 'balasan' : 'komentar';
        $tindakan = array_key_exists('is_approved', $ubah)
            ? ($ubah['is_approved'] ? 'menyetujui' : 'menyembunyikan')
            : 'menyunting';

        Activity::log(
            $tindakan.'_'.($comment->parent_id ? 'balasan' : 'komentar'),
            sprintf(
                '%s %s %s dari %s pada: %s',
                $request->user()->name,
                $tindakan,
                $jenis,
                $comment->nama,
                $comment->post?->judul ?? '-'
            ),
            [
                'severity' => 'info',
                'status' => 200,
                'subject_type' => 'Post',
                'subject_id' => (string) $comment->post_id,
                'data' => ['komentar_id' => $comment->id, 'jenis' => $jenis],
            ]
        );

        return response()->json(['data' => $comment->fresh()]);
    }

    /**
     * Hapus komentar ATAU balasan. Bila yang dihapus komentar utama,
     * seluruh balasannya ikut terhapus (ditangani di model PostComment).
     */
    public function destroy(Request $request, PostComment $comment)
    {
        if (! $this->bolehKelola($request, $comment)) {
            return $this->tolak();
        }

        $jenis = $comment->parent_id ? 'balasan' : 'komentar';
        $nama = $comment->nama;
        $judul = $comment->post?->judul ?? '-';
        $postId = $comment->post_id;
        $komentarId = $comment->id;
        $jumlahAnak = $comment->children()->count();

        $comment->delete();

        Activity::log(
            'hapus_'.$jenis,
            "{$request->user()->name} menghapus {$jenis} dari {$nama} pada: {$judul}",
            [
                'severity' => 'warning',
                'status' => 200,
                'subject_type' => 'Post',
                'subject_id' => (string) $postId,
                'data' => ['komentar_id' => $komentarId, 'balasan_ikut_terhapus' => $jumlahAnak],
            ]
        );

        return response()->json([
            'message' => $jenis === 'komentar' && $jumlahAnak > 0
                ? "Komentar beserta {$jumlahAnak} balasannya dihapus."
                : ucfirst($jenis).' dihapus.',
        ]);
    }

    /** Admin bebas; penulis hanya pada berita miliknya. */
    protected function bolehKelola(Request $request, PostComment $comment): bool
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return true;
        }

        return $comment->post && (int) $comment->post->user_id === (int) $user->id;
    }

    protected function tolak()
    {
        return response()->json([
            'message' => 'Anda hanya bisa mengelola komentar pada berita milik Anda sendiri.',
        ], 403);
    }
}
