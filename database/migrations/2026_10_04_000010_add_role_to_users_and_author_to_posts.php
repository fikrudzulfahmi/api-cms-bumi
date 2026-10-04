<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Peran akun: 'admin' (akses penuh) | 'penulis' (hanya berita miliknya)
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->after('email');
        });

        // Penulis berita
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        // Backfill: berita lama (tanpa penulis) dianggap ditulis admin pertama,
        // supaya "Oleh: ..." langsung tampil di berita yang sudah ada.
        $adminId = DB::table('users')->where('role', 'admin')->orderBy('id')->value('id');

        if ($adminId) {
            DB::table('posts')->whereNull('user_id')->update(['user_id' => $adminId]);
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
