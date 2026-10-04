<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas CMS Bumi
    |--------------------------------------------------------------------------
    | Dipakai halaman identitas backend & halaman error kustom.
    | Nilai env dibaca di sini (bukan di view) supaya aman saat config:cache.
    */

    'nama' => env('CMS_NAMA', "MA Bustanul Muta'allimin"),

    // URL website utama (frontend SPA) untuk tautan balik.
    'frontend_url' => env('FRONTEND_URL', 'https://bumi.backupmate.biz.id'),

];
