<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Kategori awal — memakai slug yang sudah dipakai berita lama supaya
 * tidak ada data yang perlu diubah. Admin bisa menambah/mengubah dari panel.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            ['slug' => 'berita', 'nama' => 'Berita', 'urutan' => 1],
            ['slug' => 'pengumuman', 'nama' => 'Pengumuman', 'urutan' => 2],
            ['slug' => 'prestasi', 'nama' => 'Prestasi', 'urutan' => 3],
        ];

        foreach ($kategori as $k) {
            Category::updateOrCreate(['slug' => $k['slug']], $k + ['is_active' => true]);
        }
    }
}
