<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log aktivitas (jejak audit) — append-only.
 *
 * Tidak ada kolom updated_at dan TIDAK ADA endpoint hapus: tujuannya agar
 * jejak tetap utuh bila suatu saat terkena hack.
 * `hash`/`prev_hash` membentuk rantai hash sehingga manipulasi baris terdeteksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Pelaku (diduplikasi agar tetap terbaca walau akunnya dihapus)
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('actor')->nullable();
            $table->string('actor_email')->nullable();

            // Apa yang terjadi
            $table->string('event', 40)->index();
            $table->string('severity', 12)->default('info')->index();
            $table->string('description', 500)->nullable();

            // Objek yang terkena dampak
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();

            // Konteks permintaan
            $table->string('method', 10)->nullable();
            $table->string('path', 255)->nullable();
            $table->string('route')->nullable();
            $table->unsignedSmallInteger('status')->nullable();

            // Konteks jaringan
            $table->string('ip', 45)->nullable()->index();
            $table->string('forwarded_for', 255)->nullable();
            $table->string('user_agent', 512)->nullable();

            // Rincian perubahan (TEXT, bukan JSON: byte harus persis agar hash stabil)
            $table->text('data')->nullable();

            // Rantai hash anti-manipulasi
            $table->string('prev_hash', 64)->nullable();
            $table->string('hash', 64)->nullable()->index();

            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
