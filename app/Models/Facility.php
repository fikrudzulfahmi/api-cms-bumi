<?php

namespace App\Models;

use App\Models\Traits\HasImage;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasImage;
    use LogsActivity;

    protected $fillable = ['nama', 'deskripsi', 'gambar'];

    protected $appends = ['gambar_url'];

    /** Nama entitas pada log aktivitas. */
    public function activityLabel(): string
    {
        return 'Fasilitas';
    }
}
