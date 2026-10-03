<?php

namespace App\Models;

use App\Models\Traits\HasImage;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasImage;

    protected $fillable = ['nama', 'deskripsi', 'gambar'];

    protected $appends = ['gambar_url'];
}
