<?php

namespace App\Models\Traits;

trait HasImage
{
    /**
     * URL lengkap untuk kolom `gambar` (jika ada).
     */
    public function getGambarUrlAttribute(): ?string
    {
        return $this->imageUrl($this->gambar ?? null);
    }

    /**
     * URL lengkap untuk kolom `foto` (jika ada).
     */
    public function getFotoUrlAttribute(): ?string
    {
        return $this->imageUrl($this->foto ?? null);
    }

    protected function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // sudah URL absolut (mis. placeholder eksternal)
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return url('storage/'.ltrim($path, '/'));
    }
}
