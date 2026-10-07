<?php

namespace App\Models\Traits;

use App\Support\GambarOptim;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Daftar varian ukuran gambar (srcset) untuk kolom `gambar`.
     * Dipakai kartu berita & gambar utama supaya HP tidak mengunduh berkas 1920px.
     * Null bila tidak ada varian (mis. gambar dari luar) — pemanggil pakai src biasa.
     */
    public function getGambarSrcsetAttribute(): ?string
    {
        return $this->srcsetUntuk($this->gambar ?? null);
    }

    protected function srcsetUntuk(?string $path): ?string
    {
        // Gambar eksternal (mis. placeholder) tidak punya varian.
        if (! $path || preg_match('#^https?://#i', $path)) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        return GambarOptim::srcset(
            $disk->path($path),
            url('storage/'.dirname(ltrim($path, '/')))
        );
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
