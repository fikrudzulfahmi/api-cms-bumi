<?php

namespace App\Models;

use App\Models\Traits\HasImage;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use HasImage;

    protected $fillable = ['judul', 'gambar', 'kategori'];

    protected $appends = ['gambar_url'];

    public const KATEGORI = ['kegiatan', 'murid', 'fasilitas'];
}
