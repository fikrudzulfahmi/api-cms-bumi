<?php

namespace App\Http\Controllers;

/**
 * Halaman pembuka (root) backend.
 *
 * Backend ini murni API — root-nya dulu menampilkan 404 bawaan Laravel.
 * Sekarang root menampilkan halaman identitas bergaya sama dengan situs.
 * Wajib berupa controller (bukan closure) agar `route:cache` tetap bisa jalan.
 */
class LandingController extends Controller
{
    public function index()
    {
        return view('backend-home', [
            'nama' => config('cms.nama'),
            'versi' => app()->version(),
            'lingkungan' => app()->environment(),
        ]);
    }
}
