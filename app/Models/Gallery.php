<?php

namespace App\Models;

use App\Models\Traits\HasImage;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use HasImage;
    use LogsActivity;

    protected $fillable = ['judul', 'gambar', 'kategori'];

    protected $appends = ['gambar_url'];

    public const KATEGORI = ['kegiatan', 'murid', 'fasilitas'];

    /** Nama entitas pada log aktivitas. */
    public function activityLabel(): string
    {
        return 'Galeri';
    }
}
