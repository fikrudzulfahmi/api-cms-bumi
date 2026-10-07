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
     * @param  bool  $kering  true = hanya mengukur (berkas asli TIDAK disentuh).
     * @param  int|null  $ukuranHasil  diisi ukuran berkas hasil (byte).
     * @return string|null path baru (berakhiran .webp) bila berhasil, null bila dibiarkan.
     */
    public static function jalankan(string $path, bool $kering = false, ?int &$ukuranHasil = null): ?string
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

        // Mode kering: hasil ditulis ke berkas sementara supaya berkas asli aman.
        $sasaran = $kering ? $path.'.uji' : $tujuan;

        $berhasil = @imagewebp($gambar, $sasaran, self::KUALITAS);
        imagedestroy($gambar);

        if (! $berhasil || ! is_file($sasaran)) {
            @unlink($sasaran);

            return null;
        }

        $ukuranBaru = (int) filesize($sasaran);
        $ukuranHasil = $ukuranBaru;

        if ($kering) {
            @unlink($sasaran);   // hanya untuk mengukur

            return $ukuranBaru < (int) filesize($path) ? $tujuan : null;
        }

        // Hanya pakai hasil WebP kalau memang lebih ringan dari aslinya.
        if ($ukuranBaru >= (int) filesize($path)) {
            @unlink($sasaran);

            return null;
        }

        if ($sasaran !== $path) {
            @unlink($path);
        }

        return $sasaran;
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

    // ---------------------------------------------------------------- VARIAN
    /**
     * Lebar varian yang dibuat (px). Varian yang lebih besar dari gambar asli
     * dilewati supaya tidak ada pembesaran (yang membuat berkas justru menggemuk).
     */
    public const VARIAN = [480, 768, 1200];

    /** Awalan nama berkas varian: nama.webp -> nama-480.webp */
    public static function namaVarian(string $path, int $lebar): string
    {
        return preg_replace('/\.[a-z0-9]+$/i', '', $path).'-'.$lebar.'.webp';
    }

    /**
     * Buat varian ukuran dari sebuah berkas gambar.
     *
     * Dipakai agar HP tidak perlu mengunduh gambar 1920px hanya untuk kartu
     * selebar ~380px. Berkas varian dibuat sekali dan dipakai selamanya
     * (dilewati bila sudah ada dan lebih baru dari sumbernya).
     *
     * @return array<int,string> peta [lebar => path absolut]
     */
    public static function buatVarian(string $path, bool $paksa = false): array
    {
        if (! self::didukung() || ! is_file($path)) {
            return [];
        }

        $info = @getimagesize($path);
        if (! $info) {
            return [];
        }

        $sumber = match ($info[2] ?? 0) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => null,
        };

        if (! $sumber) {
            return [];
        }

        $lebarAsli = imagesx($sumber);
        $tinggiAsli = imagesy($sumber);
        $hasil = [];
        $waktuSumber = (int) @filemtime($path);

        foreach (self::VARIAN as $lebar) {
            if ($lebar >= $lebarAsli) {
                continue;   // tidak perlu diperbesar
            }

            $tujuan = self::namaVarian($path, $lebar);

            if (! $paksa && is_file($tujuan) && (int) @filemtime($tujuan) >= $waktuSumber) {
                $hasil[$lebar] = $tujuan;

                continue;
            }

            $tinggi = max(1, (int) round($tinggiAsli * ($lebar / $lebarAsli)));
            $kanvas = imagecreatetruecolor($lebar, $tinggi);
            imagealphablending($kanvas, false);
            imagesavealpha($kanvas, true);
            imagecopyresampled($kanvas, $sumber, 0, 0, 0, 0, $lebar, $tinggi, $lebarAsli, $tinggiAsli);

            $ok = @imagewebp($kanvas, $tujuan, self::KUALITAS);
            imagedestroy($kanvas);

            if ($ok && is_file($tujuan)) {
                $hasil[$lebar] = $tujuan;
            }
        }

        imagedestroy($sumber);

        return $hasil;
    }

    /**
     * Susun atribut srcset untuk sebuah berkas (hanya varian yang ada).
     * Mengembalikan null bila tidak ada varian — pemanggil memakai src biasa.
     */
    public static function srcset(string $path, string $urlDasar): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $ukuran = @getimagesize($path);
        $kandidat = [];

        foreach (self::VARIAN as $lebar) {
            $varian = self::namaVarian($path, $lebar);

            if (is_file($varian)) {
                $kandidat[] = rtrim($urlDasar, '/').'/'.basename($varian).' '.$lebar.'w';
            }
        }

        if (! $kandidat) {
            return null;
        }

        // Berkas utama jadi kandidat terbesar (lebar aslinya, bukan tebakan).
        $lebarUtama = $ukuran ? (int) $ukuran[0] : 0;
        $kandidat[] = rtrim($urlDasar, '/').'/'.basename($path).' '.$lebarUtama.'w';

        return implode(', ', $kandidat);
    }
}
