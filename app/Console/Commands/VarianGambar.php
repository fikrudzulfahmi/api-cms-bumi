<?php

namespace App\Console\Commands;

use App\Support\GambarOptim;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Membuat varian ukuran gambar (480/768/1200 px) untuk semua unggahan.
 *
 * Gunanya: kartu berita hanya selebar ~380 px, jadi HP tidak perlu mengunduh
 * gambar 1920 px. Berkas varian dibuat sekali, lalu dipakai selamanya lewat
 * atribut srcset. Perintah ini aman dijalankan berulang (varian yang sudah ada
 * dan lebih baru dari sumbernya akan dilewati).
 *
 *   php artisan gambar:varian          # proses semua
 *   php artisan gambar:varian --dry    # hanya menghitung, tidak menulis
 *   php artisan gambar:varian --paksa  # buat ulang walau sudah ada
 */
class VarianGambar extends Command
{
    protected $signature = 'gambar:varian {--dry : Hanya menghitung, tidak menulis berkas} {--paksa : Buat ulang varian yang sudah ada}';

    protected $description = 'Membuat varian ukuran gambar untuk srcset (agar HP tidak mengunduh gambar besar)';

    public function handle(): int
    {
        if (! GambarOptim::didukung()) {
            $this->warn('GD/WebP tidak tersedia di server ini — dilewati.');

            return self::SUCCESS;
        }

        $disk = Storage::disk('public');
        $akar = $disk->path('uploads');
        $kering = (bool) $this->option('dry');
        $paksa = (bool) $this->option('paksa');

        if (! is_dir($akar)) {
            $this->warn('Folder uploads belum ada — dilewati.');

            return self::SUCCESS;
        }

        $diperiksa = 0;
        $dibuat = 0;
        $hemat = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($akar, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $berkas) {
            if (! $berkas->isFile()) {
                continue;
            }

            $nama = $berkas->getFilename();

            // Lewati berkas varian itu sendiri.
            if (preg_match('/-\d{3,4}\.webp$/i', $nama)) {
                continue;
            }

            if (! preg_match('/\.(jpe?g|png|webp)$/i', $nama)) {
                continue;
            }

            $info = @getimagesize($berkas->getPathname());

            // Hanya gambar yang cukup besar yang perlu varian.
            if (! $info || (int) $info[0] < 768) {
                continue;
            }

            $diperiksa++;

            if ($kering) {
                $this->line('  akan dibuat: '.str_replace($akar.'/', '', $berkas->getPathname()));
                $dibuat++;

                continue;
            }

            $hasil = GambarOptim::buatVarian($berkas->getPathname(), $paksa);

            if ($hasil) {
                $dibuat++;
                foreach ($hasil as $lebar => $jalur) {
                    if (is_file($jalur)) {
                        $hemat += max(0, (int) filesize($berkas->getPathname()) - (int) filesize($jalur));
                    }
                    $this->line(sprintf('  %s -> %dpx (%s KB)', $nama, $lebar, number_format(filesize($jalur) / 1024, 0, ',', '.')));
                }
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s: %d gambar besar diperiksa, %d dibuat variannya, perkiraan penghematan bila varian terkecil dipakai: %s MB',
            $kering ? 'PRATINJAU' : 'SELESAI',
            $diperiksa,
            $dibuat,
            number_format($hemat / 1048576, 1, ',', '.')
        ));

        return self::SUCCESS;
    }
}
