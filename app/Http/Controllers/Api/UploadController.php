<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Activity;
use App\Support\GambarOptim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * Admin — unggah gambar, simpan ke public disk.
     * Body multipart: file + dir (opsional, nama subfolder).
     *
     * Setelah tersimpan, gambar diringankan otomatis (perkecil dimensi +
     * konversi ke WebP) — padanan fitur optimasi gambar di WordPress — supaya
     * halaman tidak lambat memuat foto berukuran besar.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'dir' => 'nullable|string|max:50',
        ], [
            'file.required' => 'Pilih gambar terlebih dahulu.',
            'file.image' => 'Berkas harus berupa gambar (JPG, PNG, WEBP, atau GIF).',
            'file.mimes' => 'Format gambar harus JPG, PNG, WEBP, atau GIF.',
            'file.max' => 'Ukuran gambar terlalu besar (maksimal 5 MB).',
            'file.uploaded' => 'Gambar gagal diunggah — ukurannya melebihi batas server. Coba gambar yang lebih kecil.',
        ]);

        $dir = $request->filled('dir') ? preg_replace('/[^a-z0-9_\-]/i', '', $request->dir) : 'umum';
        $berkas = $request->file('file');
        $ukuranAsli = $berkas->getSize();

        $path = $berkas->store('uploads/'.$dir, 'public');

        // --- Optimasi: perkecil dimensi + konversi WebP -----------------------
        $absolutBaru = GambarOptim::jalankan(Storage::disk('public')->path($path));
        if ($absolutBaru) {
            $path = 'uploads/'.$dir.'/'.basename($absolutBaru);
        }
        $ukuranAkhir = (int) Storage::disk('public')->size($path);
        $hemat = $ukuranAsli > 0 ? round(100 - ($ukuranAkhir / $ukuranAsli * 100)) : 0;

        // Unggahan tidak menyentuh tabel mana pun, jadi dicatat eksplisit di sini.
        Activity::log('unggah_berkas', "Unggah berkas ke folder '{$dir}': ".basename($path), [
            'severity' => 'warning',
            'status' => 201,
            'data' => [
                'berkas' => $path,
                'ukuran_kb' => round($ukuranAkhir / 1024, 1),
                'ukuran_asli_kb' => round($ukuranAsli / 1024, 1),
                'diringankan_persen' => $hemat,
                'dikonversi_webp' => (bool) $absolutBaru,
                'tipe' => $request->file('file')->getMimeType(),
                'nama_asli' => $request->file('file')->getClientOriginalName(),
            ],
        ]);

        return response()->json([
            'data' => [
                'path' => $path,
                'url' => Storage::url($path),
                'full_url' => url('storage/'.$path),
                'ukuran_kb' => round($ukuranAkhir / 1024, 1),
                'ukuran_asli_kb' => round($ukuranAsli / 1024, 1),
                'diringankan_persen' => $hemat,
                'dikonversi_webp' => (bool) $absolutBaru,
            ],
        ], 201);
    }
}
