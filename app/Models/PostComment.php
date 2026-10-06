<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Komentar pembaca. Tampil di halaman publik hanya setelah disetujui admin
 * (`is_approved`), supaya spam tidak langsung muncul.
 */
class PostComment extends Model
{
    protected $fillable = ['post_id', 'nama', 'email', 'isi', 'is_approved', 'ip'];

    protected $casts = ['is_approved' => 'boolean'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function scopeDisetujui($query)
    {
        return $query->where('is_approved', true);
    }

    /** Nama entitas pada log aktivitas. */
    public function activityLabel(): string
    {
        return 'Komentar Berita';
    }
}
