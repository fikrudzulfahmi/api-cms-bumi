<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun bawaan: 1 admin + 1 penulis.
 * Idempoten (firstOrCreate) — aman dijalankan berulang, password yang sudah
 * diubah pengguna TIDAK akan ditimpa.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@bustanulmutaallimin.sch.id')],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'admin123')),
                'role' => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => env('PENULIS_EMAIL', 'penulis@bustanulmutaallimin.sch.id')],
            [
                'name' => env('PENULIS_NAME', 'Redaksi Berita'),
                'password' => Hash::make(env('PENULIS_PASSWORD', 'penulis123')),
                'role' => 'penulis',
            ]
        );
    }
}
