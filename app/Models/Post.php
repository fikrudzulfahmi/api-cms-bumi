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

    protected $appends = ['gambar_url', 'author_name'];

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

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeKategori($query, string $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    /** Nama entitas pada log aktivitas. */
    public function activityLabel(): string
    {
        return 'Berita';
    }
}
