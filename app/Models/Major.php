<?php

namespace App\Models;

use App\Models\Traits\HasImage;
use Illuminate\Database\Eloquent\Model;

class Major extends Model
{
    use HasImage;

    protected $fillable = ['nama', 'slug', 'deskripsi', 'gambar', 'akreditasi'];

    protected $appends = ['gambar_url'];
}
