<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Jejak audit aktivitas CMS.
 *
 * Append-only + rantai hash: setiap baris menyimpan hash dirinya (`hash`) yang
 * dihitung dari hash baris sebelumnya (`prev_hash`). Kalau ada baris yang
 * diubah/dihapus langsung di database, rantainya putus dan `verifyChain()`
 * menunjuk baris pertama yang rusak.
 */
class ActivityLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    /** Tingkat kepentingan yang dipakai di UI & ringkasan. */
    public const SEVERITY = ['info', 'warning', 'critical'];

    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->created_at ??= now();
            $log->prev_hash = static::lastHash();
            $log->hash = $log->computeHash($log->prev_hash);
        });
    }

    public static function lastHash(): string
    {
        return static::query()->orderByDesc('id')->value('hash') ?: 'GENESIS';
    }

    /**
     * Hash konten baris. Urutan kunci `data` diseragamkan supaya nilainya
     * dapat dihitung ulang dengan hasil identik.
     */
    public function computeHash(string $prevHash): string
    {
        return hash('sha256', implode('|', [
            $prevHash,
            (string) $this->user_id,
            (string) $this->actor,
            (string) $this->actor_email,
            (string) $this->event,
            (string) $this->severity,
            (string) $this->description,
            (string) $this->subject_type,
            (string) $this->subject_id,
            (string) $this->method,
            (string) $this->path,
            (string) $this->status,
            (string) $this->ip,
            (string) $this->forwarded_for,
            (string) $this->user_agent,
            self::canonicalJson($this->data),
            $this->created_at?->toDateTimeString() ?? '',
        ]));
    }

    /** JSON dengan kunci terurut (deterministik untuk hashing). */
    public static function canonicalJson($value): string
    {
        if (is_array($value)) {
            ksort($value);
            foreach ($value as $k => $v) {
                $value[$k] = is_array($v) ? json_decode(self::canonicalJson($v), true) : $v;
            }
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    /**
     * Periksa keutuhan rantai hash.
     *
     * @return array{ok: bool, total: int, rusak_di: int|null, keterangan: string}
     */
    public static function verifyChain(): array
    {
        $prev = 'GENESIS';
        $total = 0;

        foreach (static::query()->orderBy('id')->cursor() as $log) {
            $total++;
            $harus = $log->computeHash($prev);

            if ($log->hash !== $harus || $log->prev_hash !== $prev) {
                return [
                    'ok' => false,
                    'total' => $total,
                    'rusak_di' => $log->id,
                    'keterangan' => "Rantai log rusak mulai baris #{$log->id} — ada kemungkinan log dimanipulasi langsung di database.",
                ];
            }

            $prev = $log->hash;
        }

        return [
            'ok' => true,
            'total' => $total,
            'rusak_di' => null,
            'keterangan' => "Rantai utuh — {$total} baris terverifikasi.",
        ];
    }
}
