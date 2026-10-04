<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use LogsActivity;

    protected $fillable = ['key', 'value', 'group'];

    /**
     * Ambil nilai setting berdasarkan key (helper statis).
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $row = static::where('key', $key)->first();

        return $row ? $row->value : $default;
    }

    /** Nama entitas pada log aktivitas. */
    public function activityLabel(): string
    {
        return 'Pengaturan';
    }

    /** Setiap perubahan pengaturan situs layak dicatat sebagai perhatian. */
    protected function activitySeverity(string $event): string
    {
        return $event === 'buat' ? 'info' : 'warning';
    }
}
