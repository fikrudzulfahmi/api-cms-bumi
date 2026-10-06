<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Model;

/**
 * Kategori berita yang bisa ditambah/diatur sendiri lewat panel admin.
 */
class Category extends Model
{
    use LogsActivity;

    protected $fillable = ['slug', 'nama', 'urutan', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTerurut($query)
    {
        return $query->orderBy('urutan')->orderBy('nama');
    }

    /** Slug unik dari nama kategori. */
    public static function slugUnik(string $nama, ?int $kecuali = null): string
    {
        return Slug::unique(self::class, $nama, 'slug', $kecuali);
    }

    public function activityLabel(): string
    {
        return 'Kategori Berita';
    }
}
