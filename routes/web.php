<?php

use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Backend ini khusus API (lihat routes/api.php).
| Root hanya menampilkan halaman identitas backend — pakai controller
| (bukan closure) supaya `php artisan route:cache` tetap berhasil.
|
*/

Route::get('/', [LandingController::class, 'index'])->name('backend.home');
