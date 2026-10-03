<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    /**
     * Ambil nilai setting berdasarkan key (helper statis).
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $row = static::where('key', $key)->first();

        return $row ? $row->value : $default;
    }
}
