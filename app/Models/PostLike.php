<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Reaksi pembaca: satu baris per pengunjung per berita (like ATAU dislike).
 * Pengunjung boleh mengganti pilihannya — barisnya di-update, bukan ditambah.
 */
class PostLike extends Model
{
    public const LIKE = 'like';
    public const DISLIKE = 'dislike';

    public const TIPE = [self::LIKE, self::DISLIKE];

    protected $fillable = ['post_id', 'sid', 'tipe'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function scopeLike($query)
    {
        return $query->where('tipe', self::LIKE);
    }

    public function scopeDislike($query)
    {
        return $query->where('tipe', self::DISLIKE);
    }
}
