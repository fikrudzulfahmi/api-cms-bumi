<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\PostInteractionController;
use App\Http\Controllers\Api\ExtracurricularController;
use App\Http\Controllers\Api\FacilityController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\MajorController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Endpoint publik (tanpa autentikasi) — dibaca landing page
|--------------------------------------------------------------------------
*/
Route::get('/settings', [SettingController::class, 'index']);
Route::get('/profil', [ProfileController::class, 'show']);
Route::get('/guru-karyawan', [TeacherController::class, 'index']);
Route::get('/berita', [PostController::class, 'index']);
Route::get('/berita/{slug}', [PostController::class, 'show']);

// Interaksi pembaca (publik) — dibatasi agar tidak disalahgunakan.
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/berita/{slug}/view', [PostInteractionController::class, 'view']);
    Route::get('/berita/{slug}/reaksi', [PostInteractionController::class, 'reaksi']);
    Route::post('/berita/{slug}/like', [PostInteractionController::class, 'like']);
    Route::get('/berita/{slug}/komentar', [PostInteractionController::class, 'komentar']);
    Route::post('/berita/{slug}/komentar', [PostInteractionController::class, 'kirimKomentar']);
});

Route::get('/kategori', [CategoryController::class, 'index']);
Route::get('/umpan-balik', [FeedbackController::class, 'index']);
Route::get('/jurusan', [MajorController::class, 'index']);
Route::get('/jurusan/{slug}', [MajorController::class, 'show']);
Route::get('/fasilitas', [FacilityController::class, 'index']);
Route::get('/ekstrakurikuler', [ExtracurricularController::class, 'index']);
Route::get('/galeri', [GalleryController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Endpoint admin (butuh token Sanctum)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        // --- Bisa diakses admin & penulis ---
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/upload', [UploadController::class, 'store']);
        Route::put('/akun', [AuthController::class, 'updateAccount']);

        // Berita: penulis hanya bisa mengelola miliknya sendiri (dicek di controller)
        Route::get('/berita', [PostController::class, 'adminIndex']);
        Route::get('/berita/analitik', [PostController::class, 'adminAnalitik']);
        Route::post('/berita', [PostController::class, 'store']);
        Route::put('/berita/{post}', [PostController::class, 'update']);
        Route::delete('/berita/{post}', [PostController::class, 'destroy']);

        // --- Hanya admin ---
        Route::middleware('admin')->group(function () {
            // Pengaturan & profil sekolah (singleton)
            Route::put('/settings', [SettingController::class, 'update']);
            Route::put('/profil', [ProfileController::class, 'update']);

            // Akun pengguna (admin & penulis)
            Route::get('/pengguna', [UserController::class, 'index']);
            Route::post('/pengguna', [UserController::class, 'store']);
            Route::put('/pengguna/{user}', [UserController::class, 'update']);
            Route::delete('/pengguna/{user}', [UserController::class, 'destroy']);

            // Kategori berita (bisa ditambah/diatur sendiri)
            Route::get('/kategori', [CategoryController::class, 'adminIndex']);
            Route::post('/kategori', [CategoryController::class, 'store']);
            Route::put('/kategori/{category}', [CategoryController::class, 'update']);
            Route::delete('/kategori/{category}', [CategoryController::class, 'destroy']);

            // Moderasi komentar berita
            Route::get('/komentar', [CommentController::class, 'index']);
            Route::put('/komentar/{comment}', [CommentController::class, 'update']);
            Route::delete('/komentar/{comment}', [CommentController::class, 'destroy']);

            // Jejak audit — BACA SAJA (tidak ada route hapus/ubah oleh desain)
            Route::get('/log-aktivitas', [ActivityLogController::class, 'index']);
            Route::get('/log-aktivitas/ringkasan', [ActivityLogController::class, 'ringkasan']);
            Route::get('/log-aktivitas/statistik', [ActivityLogController::class, 'statistik']);
            Route::get('/log-aktivitas/verifikasi', [ActivityLogController::class, 'verifikasi']);
            Route::get('/log-aktivitas/ekspor', [ActivityLogController::class, 'ekspor']);

            // Guru & karyawan
            Route::get('/guru-karyawan', [TeacherController::class, 'index']);
            Route::post('/guru-karyawan', [TeacherController::class, 'store']);
            Route::put('/guru-karyawan/{teacher}', [TeacherController::class, 'update']);
            Route::delete('/guru-karyawan/{teacher}', [TeacherController::class, 'destroy']);

            // Umpan balik lulusan
            Route::get('/umpan-balik', [FeedbackController::class, 'index']);
            Route::post('/umpan-balik', [FeedbackController::class, 'store']);
            Route::put('/umpan-balik/{feedback}', [FeedbackController::class, 'update']);
            Route::delete('/umpan-balik/{feedback}', [FeedbackController::class, 'destroy']);

            // Jurusan
            Route::get('/jurusan', [MajorController::class, 'index']);
            Route::post('/jurusan', [MajorController::class, 'store']);
            Route::put('/jurusan/{major}', [MajorController::class, 'update']);
            Route::delete('/jurusan/{major}', [MajorController::class, 'destroy']);

            // Fasilitas
            Route::get('/fasilitas', [FacilityController::class, 'index']);
            Route::post('/fasilitas', [FacilityController::class, 'store']);
            Route::put('/fasilitas/{facility}', [FacilityController::class, 'update']);
            Route::delete('/fasilitas/{facility}', [FacilityController::class, 'destroy']);

            // Ekstrakurikuler
            Route::get('/ekstrakurikuler', [ExtracurricularController::class, 'index']);
            Route::post('/ekstrakurikuler', [ExtracurricularController::class, 'store']);
            Route::put('/ekstrakurikuler/{extracurricular}', [ExtracurricularController::class, 'update']);
            Route::delete('/ekstrakurikuler/{extracurricular}', [ExtracurricularController::class, 'destroy']);

            // Galeri
            Route::get('/galeri', [GalleryController::class, 'index']);
            Route::post('/galeri', [GalleryController::class, 'store']);
            Route::put('/galeri/{gallery}', [GalleryController::class, 'update']);
            Route::delete('/galeri/{gallery}', [GalleryController::class, 'destroy']);
        });
    });
});
