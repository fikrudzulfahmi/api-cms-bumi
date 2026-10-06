<?php

namespace App\Support;

/**
 * Optimasi gambar saat diunggah — padanan "optimasi gambar" di WordPress
 * (Smush / Imagify / ShortPixel): perkecil dimensi + konversi ke WebP.
 *
 * Dipakai pakai GD bawaan PHP supaya tidak menambah dependensi composer
 * (dan tidak menyentuh composer.lock yang dipakai produksi).
 *
 * Kalau GD/WebP tidak tersedia di server, fungsi ini hanya mengembalikan false
 * dan berkas asli tetap dipakai — unggahan tidak pernah gagal karena optimasi.
 */
class GambarOptim
{
    /** Sisi terpanjang gambar hasil optimasi (px). */
    public const SISI_MAKS = 1920;

    /** Mutu WebP (0-100). 82 sudah tajam untuk web, ukurannya jauh lebih kecil. */
    public const KUALITAS = 82;

    /** Di bawah ukuran ini tidak perlu dioptimasi (KB). */
    public const AMBANG_KECIL_KB = 80;

    /** Apakah GD + WebP tersedia? */
    public static function didukung(): bool
    {
        return extension_loaded('gd')
            && function_exists('imagewebp')
            && (imagetypes() & IMG_WEBP) !== 0;
    }

    /**
     * Optimasi berkas gambar ($path = path absolut).
     *
     * @return string|null path baru (berakhiran .webp) bila berhasil, null bila dibiarkan.
     */
    public static function jalankan(string $path): ?string
    {
        if (! self::didukung() || ! is_file($path)) {
            return null;
        }

        // Sudah ringan? tidak perlu diproses.
        if (filesize($path) < self::AMBANG_KECIL_KB * 1024) {
            return null;
        }

        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }

        $gambar = match ($info[2] ?? 0) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            // GIF (bisa animasi), BMP, dan lainnya dibiarkan apa adanya.
            default => null,
        };

        if (! $gambar) {
            return null;
        }

        $gambar = self::perbaikiOrientasi($gambar, $path, $info[2] ?? 0);

        $lebar = imagesx($gambar);
        $tinggi = imagesy($gambar);
        $skala = min(1, self::SISI_MAKS / max($lebar, $tinggi));

        if ($skala < 1) {
            $lebarBaru = max(1, (int) round($lebar * $skala));
            $tinggiBaru = max(1, (int) round($tinggi * $skala));
            $kanvas = imagecreatetruecolor($lebarBaru, $tinggiBaru);
            imagealphablending($kanvas, false);
            imagesavealpha($kanvas, true);
            imagecopyresampled($kanvas, $gambar, 0, 0, 0, 0, $lebarBaru, $tinggiBaru, $lebar, $tinggi);
            imagedestroy($gambar);
            $gambar = $kanvas;
        }

        $tujuan = preg_replace('/\.[a-z0-9]+$/i', '', $path).'.webp';
        $berhasil = @imagewebp($gambar, $tujuan, self::KUALITAS);
        imagedestroy($gambar);

        if (! $berhasil || ! is_file($tujuan)) {
            @unlink($tujuan);

            return null;
        }

        // Hanya pakai hasil WebP kalau memang lebih ringan dari aslinya.
        if (filesize($tujuan) >= filesize($path)) {
            @unlink($tujuan);

            return null;
        }

        if ($tujuan !== $path) {
            @unlink($path);
        }

        return $tujuan;
    }

    /**
     * Foto dari kamera HP sering tersimpan miring; EXIF Orientation dipakai
     * untuk memutarnya kembali supaya tampil tegak.
     */
    protected static function perbaikiOrientasi($gambar, string $path, int $tipe)
    {
        if ($tipe !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $gambar;
        }

        $exif = @exif_read_data($path);
        $derajat = match ((int) ($exif['Orientation'] ?? 0)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($derajat === 0) {
            return $gambar;
        }

        $diputar = imagerotate($gambar, $derajat, 0);

        if ($diputar) {
            imagedestroy($gambar);

            return $diputar;
        }

        return $gambar;
    }
}
