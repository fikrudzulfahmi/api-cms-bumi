<?php

namespace App\Models;

use App\Models\Traits\HasImage;
use Illuminate\Database\Eloquent\Model;

class Extracurricular extends Model
{
    use HasImage;

    protected $fillable = ['nama', 'deskripsi', 'gambar', 'pembina'];

    protected $appends = ['gambar_url'];
}
