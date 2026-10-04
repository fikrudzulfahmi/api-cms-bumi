<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use LogsActivity;

    protected $fillable = ['sejarah', 'visi', 'misi'];

    /** Nama entitas pada log aktivitas. */
    public function activityLabel(): string
    {
        return 'Profil Sekolah';
    }
}
