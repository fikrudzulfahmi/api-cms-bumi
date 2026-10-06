<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori berita yang bisa ditambah/diatur sendiri dari panel admin
 * (sebelumnya terpaku pada berita/pengumuman/prestasi di kode).
 *
 * `posts.kategori` tetap menyimpan slug (teks), jadi data lama tidak perlu diubah —
 * tabel ini hanya menjadi daftar pilihan yang sah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('nama', 80);
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
