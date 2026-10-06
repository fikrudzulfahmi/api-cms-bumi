<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Komentar berjenjang: pengunjung boleh saling membalas, begitu juga penulis/admin.
 *
 *  - parent_id → id komentar yang dibalas. NULL = komentar utama.
 *    Balasan selalu ditempel pada komentar UTAMA (maksimal 2 tingkat), jadi
 *    tampilannya rapi dan tidak menjorok tak terbatas. Bila pengunjung membalas
 *    sebuah balasan, kita simpan nama sasaran di `balas_ke` ("@Nama").
 *  - user_id   → terisi bila balasan datang dari penulis/admin yang login
 *    (dipakai untuk label "Pengelola").
 *  - balas_ke  → nama yang disapa (untuk balasan antar pengunjung).
 *
 * Kolom `balasan`/`balasan_at`/`balasan_oleh` yang lama tetap ada (tidak dihapus,
 * supaya tidak ada risiko kehilangan data) tetapi sudah tidak dipakai — isinya
 * dipindahkan ke tabel ini sebagai baris anak di bawah.
 *
 * CATATAN: kolom parent_id/user_id sengaja TANPA foreign key constraint, karena
 * SQLite (DB pengembangan) tidak bisa menambah constraint lewat ALTER TABLE.
 * Penghapusan anak ditangani di controller.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('post_id');
            $table->unsignedBigInteger('user_id')->nullable()->after('parent_id');
            $table->string('balas_ke', 80)->nullable()->after('user_id');
            $table->index('parent_id');
        });

        // Pindahkan balasan gaya lama (kolom `balasan`) menjadi baris anak.
        DB::table('post_comments')
            ->whereNotNull('balasan')
            ->where('balasan', '!=', '')
            ->orderBy('id')
            ->get()
            ->each(function ($c) {
                DB::table('post_comments')->insert([
                    'post_id' => $c->post_id,
                    'parent_id' => $c->id,
                    'user_id' => null,
                    'balas_ke' => null,
                    'nama' => $c->balasan_oleh ?: 'Pengelola',
                    'email' => null,
                    'isi' => $c->balasan,
                    'is_approved' => true,
                    'ip' => null,
                    'created_at' => $c->balasan_at ?: $c->created_at,
                    'updated_at' => $c->balasan_at ?: $c->created_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('post_comments', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            $table->dropColumn(['parent_id', 'user_id', 'balas_ke']);
        });
    }
};
