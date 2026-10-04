<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Support\Str;

/**
 * Pintu masuk tunggal untuk mencatat aktivitas.
 *
 * Aturan penting: pencatatan TIDAK BOLEH sampai menggagalkan aplikasi —
 * semua error di sini ditelan dan dilaporkan, bukan dilempar.
 */
class Activity
{
    /** Kunci yang isinya disamarkan agar tidak menyimpan rahasia. */
    private const SENSITIVE = '/pass|token|secret|api[_-]?key|remember|authorization|pin/i';

    public static function log(string $event, ?string $description = null, array $opts = []): ?ActivityLog
    {
        try {
            $user = $opts['user'] ?? auth()->user();
            $req = request();

            return ActivityLog::create([
                'user_id' => $opts['user_id'] ?? $user?->id,
                'actor' => $opts['actor'] ?? $user?->name,
                'actor_email' => $opts['actor_email'] ?? $user?->email,
                'event' => $event,
                'severity' => $opts['severity'] ?? 'info',
                'description' => $description,
                'subject_type' => $opts['subject_type'] ?? null,
                'subject_id' => $opts['subject_id'] ?? null,
                'method' => $opts['method'] ?? $req?->method(),
                'path' => $opts['path'] ?? $req?->path(),
                'route' => $opts['route'] ?? $req?->route()?->getName(),
                'status' => $opts['status'] ?? null,
                'ip' => $opts['ip'] ?? $req?->ip(),
                'forwarded_for' => $opts['forwarded_for'] ?? Str::limit((string) $req?->header('X-Forwarded-For'), 250, ''),
                'user_agent' => $opts['user_agent'] ?? Str::limit((string) $req?->userAgent(), 500, ''),
                'data' => $opts['data'] ?? null,
            ]);
        } catch (\Throwable $e) {
            // Jejak gagal dicatat ≠ aplikasi boleh ikut gagal.
            report($e);

            return null;
        }
    }

    /** Samarkan nilai rahasia sebelum disimpan. */
    public static function mask(array $data): array
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::mask($v);
            } elseif (preg_match(self::SENSITIVE, (string) $k)) {
                $data[$k] = '***';
            }
        }

        return $data;
    }

    /** Nama kelas pendek dari FQCN (mis. App\Models\Post -> Post). */
    public static function shortClass(?string $fqcn): ?string
    {
        return $fqcn ? class_basename($fqcn) : null;
    }
}
