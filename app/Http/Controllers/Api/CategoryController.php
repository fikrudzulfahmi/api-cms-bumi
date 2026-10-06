<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;

/**
 * Kategori berita — bisa ditambah/diatur sendiri dari panel admin
 * (sebelumnya terpaku pada berita/pengumuman/prestasi).
 */
class CategoryController extends Controller
{
    /** Publik — kategori aktif, untuk menu & filter di halaman berita. */
    public function index()
    {
        return response()->json(['data' => Category::aktif()->terurut()->get()]);
    }

    /** Admin — semua kategori termasuk yang dinonaktifkan. */
    public function adminIndex()
    {
        return response()->json(['data' => Category::terurut()->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        $data['slug'] = Category::slugUnik(($data['slug'] ?? '') ?: $data['nama']);

        return response()->json(['data' => Category::create($data)], 201);
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validasi($request, $category);

        if (! empty($data['slug']) && $data['slug'] !== $category->slug) {
            $data['slug'] = Category::slugUnik($data['slug'], $category->id);
        } elseif (empty($data['slug'])) {
            unset($data['slug']);
        }

        $category->update($data);

        return response()->json(['data' => $category->fresh()]);
    }

    public function destroy(Category $category)
    {
        $terpakai = Post::where('kategori', $category->slug)->count();

        if ($terpakai > 0) {
            return response()->json([
                'message' => "Kategori \"{$category->nama}\" masih dipakai {$terpakai} berita. "
                    .'Pindahkan dulu berita tersebut, atau nonaktifkan kategorinya agar tidak muncul di pilihan baru.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Kategori dihapus.']);
    }

    protected function validasi(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'nama' => 'required|string|max:80',
            'slug' => 'nullable|string|max:60',
            'urutan' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable|boolean',
        ], [
            'nama.required' => 'Nama kategori wajib diisi.',
            'nama.max' => 'Nama kategori maksimal 80 karakter.',
            'urutan.integer' => 'Urutan harus berupa angka.',
        ]);
    }
}
