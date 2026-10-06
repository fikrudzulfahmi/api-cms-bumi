<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom SEO ala Yoast: judul SEO dan deskripsi meta yang bisa ditulis admin
 * terpisah dari judul/ringkasan berita, plus kata kunci utama yang dibidik.
 *
 * Bila kosong, halaman memakai judul/ringkasan berita sebagai cadangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('meta_judul', 255)->nullable()->after('ringkasan');
            $table->string('meta_deskripsi', 320)->nullable()->after('meta_judul');
            $table->string('kata_kunci', 120)->nullable()->after('meta_deskripsi');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['meta_judul', 'meta_deskripsi', 'kata_kunci']);
        });
    }
};
