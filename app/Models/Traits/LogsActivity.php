<?php

namespace App\Models\Traits;

use App\Support\Activity;

/**
 * Mencatat otomatis pembuatan / perubahan / penghapusan model ke `activity_logs`,
 * lengkap dengan nilai sebelum & sesudah.
 *
 * Model bisa menyesuaikan dengan menimpa `activityLabel()`, `activityTitle()`,
 * `activitySeverity()`, dan `activityIgnored()`.
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            Activity::log('buat', $model->activityDescription('Dibuat'), [
                'severity' => $model->activitySeverity('buat'),
                'subject_type' => class_basename($model),
                'subject_id' => (string) $model->getKey(),
                'data' => ['baru' => Activity::mask($model->activitySnapshot())],
            ]);
        });

        static::updated(function ($model) {
            $lama = [];
            $baru = [];

            foreach ($model->getChanges() as $kolom => $nilai) {
                if (in_array($kolom, $model->activityIgnored(), true)) {
                    continue;
                }
                $lama[$kolom] = $model->getOriginal($kolom);
                $baru[$kolom] = $nilai;
            }

            if (empty($baru)) {
                return; // tidak ada yang berubah berarti
            }

            Activity::log('ubah', $model->activityDescription('Diubah'), [
                'severity' => $model->activitySeverity('ubah'),
                'subject_type' => class_basename($model),
                'subject_id' => (string) $model->getKey(),
                'data' => [
                    'sebelum' => Activity::mask($lama),
                    'sesudah' => Activity::mask($baru),
                ],
            ]);
        });

        static::deleted(function ($model) {
            Activity::log('hapus', $model->activityDescription('Dihapus'), [
                'severity' => $model->activitySeverity('hapus'),
                'subject_type' => class_basename($model),
                'subject_id' => (string) $model->getKey(),
                'data' => ['terakhir' => Activity::mask($model->activitySnapshot())],
            ]);
        });
    }

    /** Nama entitas untuk manusia, mis. "Berita". */
    public function activityLabel(): string
    {
        return class_basename($this);
    }

    /** Identitas baris (judul/nama) agar log mudah dibaca. */
    public function activityTitle(): ?string
    {
        foreach (['judul', 'nama', 'name', 'label', 'key', 'email', 'slug'] as $kolom) {
            if (! empty($this->{$kolom})) {
                return mb_substr((string) $this->{$kolom}, 0, 120);
            }
        }

        return '#'.$this->getKey();
    }

    protected function activityDescription(string $aksi): string
    {
        return $aksi.' '.$this->activityLabel().': '.$this->activityTitle();
    }

    /** Tingkat kepentingan: model sensitif boleh menaikkan ini. */
    protected function activitySeverity(string $event): string
    {
        return $event === 'hapus' ? 'warning' : 'info';
    }

    /** Kolom yang tidak perlu dicatat. */
    protected function activityIgnored(): array
    {
        return ['updated_at', 'remember_token', 'email_verified_at'];
    }

    protected function activitySnapshot(): array
    {
        return collect($this->getAttributes())
            ->except(array_merge($this->activityIgnored(), ['created_at']))
            ->toArray();
    }
}
