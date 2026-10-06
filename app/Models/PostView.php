<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Rincian kunjungan berita — satu baris per pengunjung per hari.
 * `sid` = hash IP + user agent, jadi orang yang sama tidak dihitung berkali-kali
 * pada hari yang sama (mencegah angka menggelembung karena refresh).
 */
class PostView extends Model
{
    protected $fillable = ['post_id', 'sid', 'tanggal'];

    // Sengaja TANPA cast tanggal: agar perbandingan WHERE memakai 'YYYY-MM-DD'
    // (cast date akan mengubahnya jadi datetime dan baris lama tidak ditemukan).

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
