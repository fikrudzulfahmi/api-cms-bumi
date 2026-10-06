<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Komentar pembaca. Tampil di halaman publik hanya setelah disetujui admin
 * (`is_approved`), supaya spam tidak langsung muncul.
 *
 * Penulis berita boleh membalas komentarnya lewat kolom `balasan` (satu balasan
 * resmi per komentar). Membalas otomatis menyetujui komentarnya, karena penulis
 * yang menjawab berarti komentar itu memang layak tampil.
 */
class PostComment extends Model
{
    protected $fillable = [
        'post_id', 'nama', 'email', 'isi', 'is_approved', 'ip',
        'balasan', 'balasan_at', 'balasan_oleh',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'balasan_at' => 'datetime',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function scopeDisetujui($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeSudahDibalas($query)
    {
        return $query->whereNotNull('balasan')->where('balasan', '!=', '');
    }

    public function getSudahDibalasAttribute(): bool
    {
        return filled($this->balasan);
    }

    /** Nama entitas pada log aktivitas. */
    public function activityLabel(): string
    {
        return 'Komentar Berita';
    }
}
