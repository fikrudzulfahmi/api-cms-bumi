<?php

namespace App\Console\Commands;

use App\Support\GambarOptim;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Ringankan gambar LAMA yang sudah terunggah sebelum fitur optimasi ada.
 *
 * Menjalankan optimasi yang sama seperti saat unggah (perkecil + WebP), lalu
 * memperbarui rujukan path di database supaya gambar tetap tampil.
 *
 * Contoh:
 *   php artisan gambar:optim --dry     (lihat rencana saja)
 *   php artisan gambar:optim           (jalankan sungguhan)
 */
class OptimGambar extends Command
{
    protected $signature = 'gambar:optim {--dry : hanya menampilkan rencana, tidak mengubah apa pun}';

    protected $description = 'Perkecil + konversi WebP untuk gambar lama, sekaligus perbarui rujukan di database';

    /** Tabel => kolom yang menyimpan path gambar. */
    protected array $kolomGambar = [
        'posts' => 'gambar',
        'teachers' => 'foto',
        'majors' => 'gambar',
        'facilities' => 'gambar',
        'extracurriculars' => 'gambar',
        'galleries' => 'gambar',
        'feedbacks' => 'foto',
    ];

    public function handle(): int
    {
        if (! GambarOptim::didukung()) {
            $this->error('GD/WebP tidak tersedia di server ini — optimasi dilewati.');

            return self::SUCCESS;
        }

        $disk = Storage::disk('public');
        $akar = $disk->path('uploads');

        if (! is_dir($akar)) {
            $this->warn("Folder {$akar} tidak ditemukan — tidak ada yang diproses.");

            return self::SUCCESS;
        }

        $berkas = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($akar, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $f) {
            if ($f->isFile() && preg_match('/\.(jpe?g|png|webp)$/i', $f->getFilename())) {
                $berkas[] = $f->getPathname();
            }
        }

        $kering = (bool) $this->option('dry');
        $this->info(count($berkas).' berkas gambar diperiksa'.($kering ? ' (mode uji — tidak ada yang diubah)' : ''));

        $jumlah = 0;
        $hemat = 0;

        foreach ($berkas as $path) {
            $sebelum = filesize($path);
            $relLama = $this->relatif($akar, $path);

            $absolutBaru = GambarOptim::jalankan($path, $kering, $ukuranHasil);

            if (! $absolutBaru) {
                continue;   // sudah ringan / format dilewati / gagal
            }

            $relBaru = $this->relatif($akar, $absolutBaru);
            $sesudah = (int) ($ukuranHasil ?? 0);
            $hemat += $sebelum - $sesudah;
            $jumlah++;

            $this->line(sprintf(
                '  %-42s %7.0f KB -> %6.0f KB',
                basename($relLama),
                $sebelum / 1024,
                $sesudah / 1024
            ));

            if ($kering) {
                continue;
            }

            $this->perbaruiRujukan($relLama, $relBaru);
        }

        $this->newLine();
        $this->info(sprintf(
            '%d berkas diringankan — hemat %.1f MB%s',
            $jumlah,
            $hemat / 1024 / 1024,
            $kering ? ' (belum dijalankan sungguhan)' : ''
        ));

        return self::SUCCESS;
    }

    /** Ubah path absolut menjadi path relatif terhadap disk (uploads/...). */
    protected function relatif(string $akar, string $path): string
    {
        $rel = 'uploads/'.ltrim(str_replace($akar, '', $path), '/\\');

        return str_replace('\\', '/', $rel);
    }

    /** Perbarui semua rujukan path lama -> path baru. */
    protected function perbaruiRujukan(string $lama, string $baru): void
    {
        foreach ($this->kolomGambar as $tabel => $kolom) {
            try {
                DB::table($tabel)->where($kolom, $lama)->update([$kolom => $baru]);
            } catch (\Throwable $e) {
                // tabel mungkin tidak ada di instalasi tertentu — abaikan
            }
        }

        try {
            DB::table('settings')
                ->whereIn('key', ['logo', 'hero_gambar', 'favicon'])
                ->where('value', $lama)
                ->update(['value' => $baru]);
        } catch (\Throwable $e) {
            // abaikan
        }
    }
}
