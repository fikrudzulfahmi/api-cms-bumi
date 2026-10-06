<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Major;
use App\Models\Post;

/**
 * Data ringkas untuk SEO — dipakai oleh "front controller" PHP di docroot
 * situs depan (index.php) untuk menyusun sitemap.xml dan meta halaman.
 *
 * Sengaja ringan (hanya slug/tanggal) supaya murah dipanggil dan bisa
 * disimpan di cache oleh sisi PHP.
 */
class SeoController extends Controller
{
    /** Daftar URL yang perlu ada di sitemap. */
    public function url()
    {
        return response()->json(['data' => [
            'berita' => Post::published()
                ->orderByDesc('tanggal')
                ->get(['slug', 'judul', 'tanggal', 'updated_at', 'kategori']),
            'jurusan' => Major::orderBy('id')->get(['slug', 'nama', 'updated_at']),
            'kategori' => Category::aktif()->terurut()->get(['slug', 'nama']),
        ]])->header('Cache-Control', 'public, max-age=300');
    }
}
