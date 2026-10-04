<?php

namespace App\Models;

use App\Models\Traits\HasImage;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasImage;
    use LogsActivity;

    protected $table = 'feedbacks';

    protected $fillable = ['nama', 'tahun_lulus', 'jurusan', 'pesan', 'foto'];

    protected $appends = ['foto_url'];

    /** Nama entitas pada log aktivitas. */
    public function activityLabel(): string
    {
        return 'Umpan Balik';
    }
}
