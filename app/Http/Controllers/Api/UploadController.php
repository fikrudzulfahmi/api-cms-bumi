<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * Admin — unggah gambar, simpan ke public disk.
     * Body multipart: file + dir (opsional, nama subfolder).
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:12288',
            'dir' => 'nullable|string|max:50',
        ], [
            'file.required' => 'Pilih gambar terlebih dahulu.',
            'file.image' => 'Berkas harus berupa gambar (JPG, PNG, WEBP, atau GIF).',
            'file.mimes' => 'Format gambar harus JPG, PNG, WEBP, atau GIF.',
            'file.max' => 'Ukuran gambar terlalu besar (maksimal 12 MB).',
            'file.uploaded' => 'Gambar gagal diunggah — ukurannya melebihi batas server. Coba gambar yang lebih kecil.',
        ]);

        $dir = $request->filled('dir') ? preg_replace('/[^a-z0-9_\-]/i', '', $request->dir) : 'umum';

        $path = $request->file('file')->store('uploads/'.$dir, 'public');

        // Unggahan tidak menyentuh tabel mana pun, jadi dicatat eksplisit di sini.
        Activity::log('unggah_berkas', "Unggah berkas ke folder '{$dir}': ".basename($path), [
            'severity' => 'warning',
            'status' => 201,
            'data' => [
                'berkas' => $path,
                'ukuran_kb' => round($request->file('file')->getSize() / 1024, 1),
                'tipe' => $request->file('file')->getMimeType(),
                'nama_asli' => $request->file('file')->getClientOriginalName(),
            ],
        ]);

        return response()->json([
            'data' => [
                'path' => $path,
                'url' => Storage::url($path),
                'full_url' => url('storage/'.$path),
            ],
        ], 201);
    }
}
