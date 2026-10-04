<?php

use App\Support\Activity;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);

        // Jejak audit (respons biasa): 403 dari EnsureAdmin dan error 5xx.
        $middleware->api(append: [
            \App\Http\Middleware\LogAdminActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Jejak audit untuk penolakan berbentuk EXCEPTION.
         *
         * Exception dilempar melewati pipeline middleware (Laravel mengurutkan
         * `auth` lebih awal), jadi 401 tanpa token, 404 penebakan alamat admin,
         * dan 500 hanya bisa tertangkap di sini.
         *
         * Callback ini selalu mengembalikan null → respons tetap dibuat Laravel
         * seperti biasa; kita hanya mencatat.
         */
        $exceptions->render(function (Throwable $e, $request) {
            try {
                if (! $request->is('api/*')) {
                    return null;
                }

                $status = match (true) {
                    $e instanceof AuthenticationException => 401,
                    $e instanceof ValidationException => 422,
                    $e instanceof NotFoundHttpException => 404,
                    $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                    default => 500,
                };

                // Validasi gagal = urusan pemakaian, bukan sinyal keamanan.
                if ($status === 422) {
                    return null;
                }

                // 404 alamat publik itu wajar; yang dipantau hanya alamat admin.
                if ($status === 404 && ! $request->is('api/admin/*')) {
                    return null;
                }

                // Login gagal dicatat lebih rinci oleh AuthController.
                if ($status === 401 && $request->is('api/admin/login')) {
                    return null;
                }

                $aksi = $request->method().' '.$request->path();
                $user = $request->user();

                if (in_array($status, [401, 403], true)) {
                    Activity::log('akses_ditolak', "Akses ditolak ({$status}): {$aksi}", [
                        'severity' => 'critical',
                        'status' => $status,
                        'user_id' => $user?->id,
                        'actor' => $user?->name,
                        'actor_email' => $user?->email,
                        'data' => [
                            'catatan' => $user
                                ? 'Token sah, tetapi perannya tidak berhak atas aksi ini.'
                                : 'Tanpa token/akun sah — kemungkinan percobaan menerobos API.',
                            'pengecualian' => class_basename($e),
                        ],
                    ]);
                } elseif ($status === 404) {
                    Activity::log('rute_tidak_ditemukan', "Menebak alamat admin (404): {$aksi}", [
                        'severity' => 'warning',
                        'status' => 404,
                    ]);
                } elseif ($status >= 500) {
                    Activity::log('error_server', "Error {$status} pada {$aksi}", [
                        'severity' => 'warning',
                        'status' => $status,
                        'data' => ['pengecualian' => class_basename($e)],
                    ]);
                }
            } catch (Throwable $x) {
                report($x);
            }

            return null;
        });
    })->create();
