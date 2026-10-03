<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    /**
     * Publik — galeri (foto murid / kegiatan).
     */
    public function index(Request $request)
    {
        $query = Gallery::latest('id');

        if ($request->filled('kategori') && in_array($request->kategori, Gallery::KATEGORI, true)) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('limit')) {
            return response()->json(['data' => $query->limit((int) $request->limit)->get()]);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => Gallery::create($request->validate($this->rules()))], 201);
    }

    public function update(Request $request, Gallery $gallery)
    {
        $gallery->update($request->validate($this->rules()));

        return response()->json(['data' => $gallery->fresh()]);
    }

    public function destroy(Gallery $gallery)
    {
        $gallery->delete();

        return response()->json(['message' => 'Galeri dihapus.']);
    }

    protected function rules(): array
    {
        return [
            'judul' => 'nullable|string|max:255',
            'gambar' => 'required|string|max:500',
            'kategori' => 'required|in:kegiatan,murid,fasilitas',
        ];
    }
}
