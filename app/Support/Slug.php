<?php

namespace App\Support;

use Illuminate\Support\Str;

class Slug
{
    /**
     * Buat slug unik untuk kolom slug pada model tertentu.
     */
    public static function unique(string $modelClass, string $text, string $column = 'slug', ?int $ignoreId = null): string
    {
        $slug = Str::slug($text);
        if ($slug === '') {
            $slug = 'item';
        }

        $original = $slug;
        $counter = 2;

        while ($modelClass::where($column, $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
