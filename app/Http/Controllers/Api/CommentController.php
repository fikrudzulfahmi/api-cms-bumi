<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PostComment;
use Illuminate\Http\Request;

/**
 * Moderasi komentar berita (admin).
 */
class CommentController extends Controller
{
    public function index(Request $request)
    {
        $query = PostComment::with('post:id,judul,slug')->latest('id');

        if ($request->filled('status')) {
            $query->where('is_approved', $request->input('status') === 'disetujui');
        }

        if ($request->filled('post_id')) {
            $query->where('post_id', (int) $request->input('post_id'));
        }

        if ($request->filled('q')) {
            $cari = $request->input('q');
            $query->where(function ($w) use ($cari) {
                $w->where('nama', 'like', "%{$cari}%")->orWhere('isi', 'like', "%{$cari}%");
            });
        }

        return response()->json($query->paginate(min((int) $request->input('per_page', 30), 200)));
    }

    public function update(Request $request, PostComment $comment)
    {
        $data = $request->validate(
            ['is_approved' => 'required|boolean', 'isi' => 'nullable|string|max:1500'],
            ['is_approved.required' => 'Status persetujuan wajib diisi.']
        );

        $comment->update($data);

        return response()->json(['data' => $comment->fresh()]);
    }

    public function destroy(PostComment $comment)
    {
        $comment->delete();

        return response()->json(['message' => 'Komentar dihapus.']);
    }
}
