<?php

namespace App\Models;

use App\Models\Traits\HasImage;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasImage;

    protected $fillable = ['nama', 'jabatan', 'foto', 'urutan'];

    protected $appends = ['foto_url'];
}
