<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Batasi panjang kolom string ke 191 agar index aman di MySQL/MariaDB
        // shared hosting (baris format COMPACT / MyISAM default).
        Schema::defaultStringLength(191);
    }
}
