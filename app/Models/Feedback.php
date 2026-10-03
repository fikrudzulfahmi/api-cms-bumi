<?php

namespace App\Models;

use App\Models\Traits\HasImage;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasImage;

    protected $table = 'feedbacks';

    protected $fillable = ['nama', 'tahun_lulus', 'jurusan', 'pesan', 'foto'];

    protected $appends = ['foto_url'];
}
