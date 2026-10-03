<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            'file' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'dir' => 'nullable|string|max:50',
        ]);

        $dir = $request->filled('dir') ? preg_replace('/[^a-z0-9_\-]/i', '', $request->dir) : 'umum';

        $path = $request->file('file')->store('uploads/'.$dir, 'public');

        return response()->json([
            'data' => [
                'path' => $path,
                'url' => Storage::url($path),
                'full_url' => url('storage/'.$path),
            ],
        ], 201);
    }
}
