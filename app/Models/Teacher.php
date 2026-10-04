<?php

namespace App\Models;

use App\Models\Traits\HasImage;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasImage;
    use LogsActivity;

    protected $fillable = ['nama', 'jabatan', 'foto', 'urutan'];

    protected $appends = ['foto_url'];

    /** Nama entitas pada log aktivitas. */
    public function activityLabel(): string
    {
        return 'Guru & Karyawan';
    }
}
