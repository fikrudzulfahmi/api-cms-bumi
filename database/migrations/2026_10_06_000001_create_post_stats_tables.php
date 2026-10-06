<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Statistik interaksi berita: pengunjung (views), like/dislike, dan komentar.
 *
 * - `posts.views` = penghitung cepat total pengunjung (di-update lewat query
 *   builder supaya TIDAK memicu event model & tidak mengotori log aktivitas).
 * - `post_views`  = rincian kunjungan, satu baris per pengunjung per hari
 *   (sid = hash IP + user agent) sehingga refresh berulang tidak menggelembungkan angka.
 * - `post_likes`  = satu reaksi per pengunjung per berita (like ATAU dislike).
 * - `post_comments` = komentar pembaca, tampil setelah disetujui admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('views')->default(0)->after('is_published');
        });

        Schema::create('post_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->string('sid', 64);
            $table->date('tanggal');
            $table->timestamps();

            $table->unique(['post_id', 'sid', 'tanggal']);
            $table->index(['tanggal']);
        });

        Schema::create('post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->string('sid', 64);
            $table->string('tipe', 8);          // like | dislike
            $table->timestamps();

            $table->unique(['post_id', 'sid']);
        });

        Schema::create('post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->string('nama', 80);
            $table->string('email', 120)->nullable();
            $table->text('isi');
            $table->boolean('is_approved')->default(false);
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['post_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_comments');
        Schema::dropIfExists('post_likes');
        Schema::dropIfExists('post_views');

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('views');
        });
    }
};
