<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Membaca jejak audit.
 *
 * Sengaja TIDAK ada endpoint hapus/ubah: log bersifat append-only.
 * Satu-satunya cara menghapusnya adalah langsung di database — dan itu akan
 * memutus rantai hash sehingga terdeteksi oleh `verifikasi`.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 30), 200);

        return response()->json(
            $this->filtered($request)->orderByDesc('id')->paginate($perPage)
        );
    }

    /** Ringkasan cepat untuk dipantau. */
    public function ringkasan()
    {
        $sejak = now()->subDay();

        return response()->json(['data' => [
            'total_24jam' => ActivityLog::where('created_at', '>=', $sejak)->count(),
            'critical_24jam' => ActivityLog::where('severity', 'critical')->where('created_at', '>=', $sejak)->count(),
            'warning_24jam' => ActivityLog::where('severity', 'warning')->where('created_at', '>=', $sejak)->count(),
            'login_gagal_24jam' => ActivityLog::where('event', 'login_gagal')->where('created_at', '>=', $sejak)->count(),
            'unggah_24jam' => ActivityLog::where('event', 'unggah_berkas')->where('created_at', '>=', $sejak)->count(),
            'ip_teratas' => ActivityLog::select('ip', DB::raw('count(*) as jumlah'))
                ->where('created_at', '>=', $sejak)
                ->whereNotNull('ip')
                ->groupBy('ip')
                ->orderByDesc('jumlah')
                ->limit(5)
                ->get(),
            'kejadian_penting' => ActivityLog::where('severity', 'critical')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'rantai' => ActivityLog::verifyChain(),
        ]]);
    }

    /** Nama entitas yang ramah dibaca (subject_type di log = nama kelas model). */
    public const JENIS = [
        'Post' => 'Berita',
        'Teacher' => 'Guru & Karyawan',
        'Major' => 'Jurusan',
        'Facility' => 'Fasilitas',
        'Extracurricular' => 'Ekstrakurikuler',
        'Gallery' => 'Galeri',
        'Feedback' => 'Umpan Balik',
        'Profile' => 'Profil Sekolah',
        'Setting' => 'Pengaturan',
        'User' => 'Akun Pengguna',
    ];

    /**
     * Data untuk beranda panel admin: apa yang terjadi, siapa yang mengerjakan,
     * dan berapa banyak konten yang dibuat/diubah/dihapus.
     */
    public function statistik(Request $request)
    {
        $hari = min(max((int) $request->input('hari', 30), 1), 365);
        $sejak = now()->subDays($hari)->startOfDay();

        $mentah = ActivityLog::select('subject_type', 'event', DB::raw('count(*) as jumlah'))
            ->where('created_at', '>=', $sejak)
            ->whereIn('event', ['buat', 'ubah', 'hapus'])
            ->whereNotNull('subject_type')
            ->groupBy('subject_type', 'event')
            ->get()
            ->groupBy('subject_type');

        $perJenis = $mentah
            ->map(function ($grup, $jenis) {
                $ambil = fn ($ev) => (int) $grup->where('event', $ev)->sum('jumlah');

                return [
                    'jenis' => self::JENIS[$jenis] ?? $jenis,
                    'kode' => $jenis,
                    'buat' => $ambil('buat'),
                    'ubah' => $ambil('ubah'),
                    'hapus' => $ambil('hapus'),
                    'total' => $ambil('buat') + $ambil('ubah') + $ambil('hapus'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        // Aktivitas harian 14 hari terakhir untuk grafik batang ringkas.
        $perHari = ActivityLog::select(DB::raw('DATE(created_at) as tanggal'), DB::raw('count(*) as jumlah'))
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('tanggal')
            ->pluck('jumlah', 'tanggal');

        $harian = [];
        for ($i = 13; $i >= 0; $i--) {
            $tanggal = now()->subDays($i)->toDateString();
            $harian[] = ['tanggal' => $tanggal, 'jumlah' => (int) ($perHari[$tanggal] ?? 0)];
        }

        $sehari = now()->subDay();

        return response()->json(['data' => [
            'rentang_hari' => $hari,
            'per_jenis' => $perJenis,
            'total' => [
                'buat' => (int) $perJenis->sum('buat'),
                'ubah' => (int) $perJenis->sum('ubah'),
                'hapus' => (int) $perJenis->sum('hapus'),
            ],
            'harian' => $harian,
            'terbaru' => ActivityLog::orderByDesc('id')->limit(10)->get(),
            'pantau' => [
                'total_24jam' => ActivityLog::where('created_at', '>=', $sehari)->count(),
                'critical_24jam' => ActivityLog::where('severity', 'critical')->where('created_at', '>=', $sehari)->count(),
                'warning_24jam' => ActivityLog::where('severity', 'warning')->where('created_at', '>=', $sehari)->count(),
                'login_gagal_24jam' => ActivityLog::where('event', 'login_gagal')->where('created_at', '>=', $sehari)->count(),
                'unggah_24jam' => ActivityLog::where('event', 'unggah_berkas')->where('created_at', '>=', $sehari)->count(),
                'pengguna_24jam' => ActivityLog::where('created_at', '>=', $sehari)->whereNotNull('user_id')->distinct()->count('user_id'),
            ],
            'rantai' => ActivityLog::verifyChain(),
        ]]);
    }

    /** Periksa keutuhan rantai hash (deteksi manipulasi log). */
    public function verifikasi()
    {
        return response()->json(['data' => ActivityLog::verifyChain()]);
    }

    /** Unduh jejak untuk pemeriksaan lanjutan (mis. saat insiden). */
    public function ekspor(Request $request): StreamedResponse
    {
        $baris = $this->filtered($request)->orderByDesc('id')->limit(20000)->get();

        // Mengunduh jejak audit itu sendiri layak dicatat.
        Activity::log('ekspor_log', 'Mengunduh jejak audit ('.$baris->count().' baris)', [
            'severity' => 'warning',
            'status' => 200,
        ]);

        $nama = 'log-aktivitas-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($baris) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar rapi di Excel

            fputcsv($out, [
                'waktu', 'severity', 'event', 'aktor', 'email', 'peran_id',
                'deskripsi', 'objek', 'objek_id', 'metode', 'path',
                'status', 'ip', 'forwarded_for', 'user_agent', 'data', 'hash',
            ]);

            foreach ($baris as $l) {
                fputcsv($out, [
                    $l->created_at?->format('Y-m-d H:i:s'),
                    $l->severity,
                    $l->event,
                    $l->actor,
                    $l->actor_email,
                    $l->user_id,
                    $l->description,
                    $l->subject_type,
                    $l->subject_id,
                    $l->method,
                    $l->path,
                    $l->status,
                    $l->ip,
                    $l->forwarded_for,
                    $l->user_agent,
                    $l->data ? json_encode($l->data, JSON_UNESCAPED_UNICODE) : '',
                    $l->hash,
                ]);
            }

            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function filtered(Request $request)
    {
        return ActivityLog::query()
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->input('event')))
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->input('severity')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->input('user_id')))
            ->when($request->filled('ip'), fn ($q) => $q->where('ip', 'like', '%'.$request->input('ip').'%'))
            ->when($request->filled('dari'), fn ($q) => $q->where('created_at', '>=', $request->input('dari').' 00:00:00'))
            ->when($request->filled('sampai'), fn ($q) => $q->where('created_at', '<=', $request->input('sampai').' 23:59:59'))
            ->when($request->filled('q'), function ($q) use ($request) {
                $t = $request->input('q');
                $q->where(function ($w) use ($t) {
                    $w->where('description', 'like', "%{$t}%")
                        ->orWhere('actor', 'like', "%{$t}%")
                        ->orWhere('actor_email', 'like', "%{$t}%")
                        ->orWhere('path', 'like', "%{$t}%")
                        ->orWhere('ip', 'like', "%{$t}%")
                        ->orWhere('event', 'like', "%{$t}%");
                });
            });
    }
}
