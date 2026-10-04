<?php

namespace App\Http\Middleware;

use App\Support\Activity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jejak audit — bagian "respons normal".
 *
 * Perubahan data dicatat otomatis oleh trait `LogsActivity`.
 * Di sini dicatat penolakan yang dikembalikan sebagai respons biasa
 * (mis. 403 dari EnsureAdmin) dan error server.
 *
 * Penolakan yang berbentuk EXCEPTION (401 tanpa token, 404 alamat admin, 500)
 * ditangani di `bootstrap/app.php`, karena exception dilempar melewati
 * middleware (Laravel memprioritaskan `auth` lebih awal).
 */
class LogAdminActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if (! $request->is('api/*')) {
                return $response;
            }

            $status = $response->getStatusCode();
            $aksi = $request->method().' '.$request->path();

            if (in_array($status, [401, 403], true)) {
                // Login gagal punya catatan khusus yang lebih rinci.
                if (! $request->is('api/admin/login')) {
                    $user = $request->user();

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
                        ],
                    ]);
                }
            } elseif ($status >= 500) {
                Activity::log('error_server', "Error {$status} pada {$aksi}", [
                    'severity' => 'warning',
                    'status' => $status,
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }
}
