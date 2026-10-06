<?php

namespace App\Models;

use App\Models\Traits\HasImage;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasImage;
    use LogsActivity;

    protected $fillable = [
        'user_id', 'judul', 'slug', 'kategori', 'gambar', 'ringkasan', 'konten', 'tanggal', 'is_published',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'is_published' => 'boolean',
    ];

    protected $appends = ['gambar_url', 'author_name', 'rating'];

    /** Kategori bawaan — hanya dipakai bila tabel `categories` belum terisi. */
    public const KATEGORI = ['berita', 'pengumuman', 'prestasi'];

    /** Penulis berita. */
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Nama penulis (dipakai publik: "Oleh: ..."). */
    public function getAuthorNameAttribute(): ?string
    {
        return $this->author?->name;
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function komentar()
    {
        return $this->hasMany(PostComment::class);
    }

    public function kunjungan()
    {
        return $this->hasMany(PostView::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeKategori($query, string $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    /** Berita paling banyak dibaca. */
    public function scopeTrending($query)
    {
        return $query->orderByDesc('views')->orderByDesc('id');
    }

    /**
     * Daftar slug kategori yang sah.
     * Diambil dari tabel `categories`; kalau tabelnya belum ada/kosong,
     * pakai daftar bawaan supaya aplikasi tetap jalan.
     */
    public static function daftarKategori(): array
    {
        try {
            $slug = Category::aktif()->orderBy('urutan')->pluck('slug')->all();

            return $slug ?: self::KATEGORI;
        } catch (\Throwable $e) {
            return self::KATEGORI;
        }
    }

    /** Jumlah suka (pakai hasil withCount bila tersedia). */
    public function getJumlahLikeAttribute(): int
    {
        return $this->angka('jumlah_like', fn () => $this->likes()->like()->count());
    }

    /** Jumlah tidak suka. */
    public function getJumlahDislikeAttribute(): int
    {
        return $this->angka('jumlah_dislike', fn () => $this->likes()->dislike()->count());
    }

    /** Jumlah komentar yang sudah disetujui. */
    public function getJumlahKomentarAttribute(): int
    {
        return $this->angka('jumlah_komentar', fn () => $this->komentar()->disetujui()->count());
    }

    /**
     * Rating bintang 1–5 dari tanggapan pembaca, popularitas, dan diskusi.
     *
     * Rumusnya sengaja dibuat sederhana dan bisa dijelaskan:
     *   dasar 3 bintang (netral)
     *   + tanggapan pembaca : (rasio suka - 0,5) x 3   → -1,5 .. +1,5 bintang
     *   + popularitas       : min(penayangan/500, 1) x 1  → 0 .. +1 bintang
     *   + diskusi           : min(komentar/20, 1) x 0,5   → 0 .. +0,5 bintang
     * Hasilnya dibatasi 1–5 bintang.
     */
    public function getRatingAttribute(): float
    {
        $suka = $this->jumlah_like;
        $tidakSuka = $this->jumlah_dislike;
        $komentar = $this->jumlah_komentar;
        $dilihat = (int) ($this->attributes['views'] ?? 0);

        $nilai = 3.0;

        $totalReaksi = $suka + $tidakSuka;
        if ($totalReaksi > 0) {
            $nilai += (($suka / $totalReaksi) - 0.5) * 3.0;
        }

        $nilai += min($dilihat / 500, 1) * 1.0;
        $nilai += min($komentar / 20, 1) * 0.5;

        return round(max(1.0, min(5.0, $nilai)), 1);
    }

    /** Ambil angka dari hasil withCount, kalau tidak ada hitung langsung. */
    protected function angka(string $atribut, callable $hitung): int
    {
        if (array_key_exists($atribut, $this->attributes)) {
            return (int) $this->attributes[$atribut];
        }

        return (int) $hitung();
    }

    /** Nama entitas pada log aktivitas. */
    public function activityLabel(): string
    {
        return 'Berita';
    }
}
