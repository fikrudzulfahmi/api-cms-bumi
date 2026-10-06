<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Balasan penulis/pengelola pada komentar pengunjung.
 * Satu komentar punya paling banyak satu balasan resmi (cukup untuk percakapan
 * tanya-jawab di situs madrasah, tanpa membuka komentar berantai yang sulit dijaga).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_comments', function (Blueprint $table) {
            $table->text('balasan')->nullable()->after('isi');
            $table->timestamp('balasan_at')->nullable()->after('balasan');
            $table->string('balasan_oleh', 80)->nullable()->after('balasan_at');
        });
    }

    public function down(): void
    {
        Schema::table('post_comments', function (Blueprint $table) {
            $table->dropColumn(['balasan', 'balasan_at', 'balasan_oleh']);
        });
    }
};
