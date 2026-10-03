<?php

namespace App\Models;

use App\Models\Traits\HasImage;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasImage;

    protected $fillable = [
        'judul', 'slug', 'kategori', 'gambar', 'ringkasan', 'konten', 'tanggal', 'is_published',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'is_published' => 'boolean',
    ];

    protected $appends = ['gambar_url'];

    public const KATEGORI = ['berita', 'pengumuman', 'prestasi'];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeKategori($query, string $kategori)
    {
        return $query->where('kategori', $kategori);
    }
}
