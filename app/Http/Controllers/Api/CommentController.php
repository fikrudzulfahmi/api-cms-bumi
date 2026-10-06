<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PostComment;
use App\Support\Activity;
use Illuminate\Http\Request;

/**
 * Moderasi + balasan komentar berita.
 *
 * Hak akses:
 *  - ADMIN   → semua komentar, boleh setujui/sembunyikan/hapus/balas.
 *  - PENULIS → hanya komentar pada berita MILIKNYA SENDIRI; boleh balas, setujui,
 *              sembunyikan, dan hapus. Semua tercatat di log aktivitas, jadi admin
 *              tetap bisa menelusuri tindakan penulis.
 *  - Selain itu → 403.
 */
class CommentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = PostComment::with('post:id,judul,slug,user_id')->latest('id');

        // Penulis hanya melihat komentar pada beritanya sendiri.
        if (! $user->isAdmin()) {
            $query->whereHas('post', fn ($q) => $q->where('user_id', $user->id));
        }

        if ($request->filled('status')) {
            $query->where('is_approved', $request->input('status') === 'disetujui');
        }

        if ($request->filled('dibalas')) {
            $request->input('dibalas') === 'sudah'
                ? $query->sudahDibalas()
                : $query->where(fn ($q) => $q->whereNull('balasan')->orWhere('balasan', ''));
        }

        if ($request->filled('post_id')) {
            $query->where('post_id', (int) $request->input('post_id'));
        }

        if ($request->filled('q')) {
            $cari = $request->input('q');
            $query->where(function ($w) use ($cari) {
                $w->where('nama', 'like', "%{$cari}%")
                    ->orWhere('isi', 'like', "%{$cari}%")
                    ->orWhere('balasan', 'like', "%{$cari}%");
            });
        }

        return response()->json($query->paginate(min((int) $request->input('per_page', 30), 200)));
    }

    /** Balas komentar pengunjung (penulis berita atau admin). */
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

        $komentarLama = $comment->sudah_dibalas ? $comment->balasan : null;

        $comment->update([
            'balasan' => $data['balasan'],
            'balasan_at' => now(),
            'balasan_oleh' => $request->user()->name,
            // Penulis/pengelola menjawab = komentar ini pantas tampil.
            'is_approved' => true,
        ]);

        Activity::log(
            $komentarLama ? 'ubah_balasan' : 'balas_komentar',
            sprintf(
                '%s %s komentar dari %s pada: %s',
                $request->user()->name,
                $komentarLama ? 'memperbarui balasan' : 'membalas',
                $comment->nama,
                $comment->post?->judul ?? '-'
            ),
            [
                'severity' => 'info',
                'status' => 200,
                'subject_type' => 'Post',
                'subject_id' => (string) $comment->post_id,
                'data' => ['komentar_id' => $comment->id, 'panjang_balasan' => strlen($data['balasan'])],
            ]
        );

        return response()->json([
            'data' => $comment->fresh()->load('post:id,judul,slug,user_id'),
            'message' => $komentarLama ? 'Balasan diperbarui.' : 'Balasan terkirim dan komentar kini tampil di situs.',
        ]);
    }

    /** Hapus balasan saja — komentar pengunjung tetap ada. */
    public function hapusBalasan(Request $request, PostComment $comment)
    {
        if (! $this->bolehKelola($request, $comment)) {
            return $this->tolak();
        }

        $comment->update(['balasan' => null, 'balasan_at' => null, 'balasan_oleh' => null]);

        return response()->json(['data' => $comment->fresh(), 'message' => 'Balasan dihapus.']);
    }

    /** Setujui / sembunyikan, atau ubah isi komentar. */
    public function update(Request $request, PostComment $comment)
    {
        if (! $this->bolehKelola($request, $comment)) {
            return $this->tolak();
        }

        $data = $request->validate(
            ['is_approved' => 'required|boolean', 'isi' => 'nullable|string|max:1500'],
            ['is_approved.required' => 'Status persetujuan wajib diisi.']
        );

        $comment->update($data);

        Activity::log(
            $data['is_approved'] ? 'setujui_komentar' : 'sembunyikan_komentar',
            sprintf(
                '%s %s komentar dari %s pada: %s',
                $request->user()->name,
                $data['is_approved'] ? 'menyetujui' : 'menyembunyikan',
                $comment->nama,
                $comment->post?->judul ?? '-'
            ),
            [
                'severity' => 'info',
                'status' => 200,
                'subject_type' => 'Post',
                'subject_id' => (string) $comment->post_id,
                'data' => ['komentar_id' => $comment->id],
            ]
        );

        return response()->json(['data' => $comment->fresh()]);
    }

    public function destroy(Request $request, PostComment $comment)
    {
        if (! $this->bolehKelola($request, $comment)) {
            return $this->tolak();
        }

        $judul = $comment->post?->judul ?? '-';
        $nama = $comment->nama;
        $postId = $comment->post_id;
        $komentarId = $comment->id;

        $comment->delete();

        Activity::log(
            'hapus_komentar',
            "{$request->user()->name} menghapus komentar dari {$nama} pada: {$judul}",
            [
                'severity' => 'warning',
                'status' => 200,
                'subject_type' => 'Post',
                'subject_id' => (string) $postId,
                'data' => ['komentar_id' => $komentarId],
            ]
        );

        return response()->json(['message' => 'Komentar dihapus.']);
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
