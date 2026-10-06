<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Komentar pembaca + balasannya (berjenjang, maksimal 2 tingkat).
 *
 *  - parent_id NULL  → komentar utama.
 *  - parent_id terisi → balasan; selalu ditempel ke komentar UTAMA.
 *  - balas_ke         → nama yang disapa, untuk balasan antar pengunjung ("@Nama").
 *  - user_id terisi   → balasan dari penulis/admin yang login (label "Pengelola").
 *
 * Komentar publik hanya tampil bila `is_approved` (moderasi admin/penulis).
 */
class PostComment extends Model
{
    protected $fillable = [
        'post_id', 'parent_id', 'user_id', 'balas_ke',
        'nama', 'email', 'isi', 'is_approved', 'ip',
        'balasan', 'balasan_at', 'balasan_oleh', // kolom lama, tidak dipakai lagi
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'balasan_at' => 'datetime',
    ];

    protected $appends = ['is_pengelola'];

    /** Menghapus komentar utama ikut menghapus seluruh balasannya. */
    protected static function booted(): void
    {
        static::deleting(function (self $komentar) {
            foreach ($komentar->children()->get() as $anak) {
                $anak->delete();
            }
        });
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDisetujui($query)
    {
        return $query->where('is_approved', true);
    }

    /** Komentar utama saja. */
    public function scopeInduk($query)
    {
        return $query->whereNull('parent_id');
    }

    /** Balasan saja. */
    public function scopeBalasan($query)
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeSudahDibalas($query)
    {
        return $query->whereHas('children');
    }

    /** Balasan dari penulis/admin (bukan pengunjung). */
    public function getIsPengelolaAttribute(): bool
    {
        return ! is_null($this->attributes['user_id'] ?? null);
    }

    /** Nama entitas pada log aktivitas. */
    public function activityLabel(): string
    {
        return 'Komentar Berita';
    }
}
