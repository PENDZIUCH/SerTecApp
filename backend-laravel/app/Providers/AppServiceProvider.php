<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\WoPartUsed;
use App\Observers\WoPartsUsedObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        WoPartUsed::observe(WoPartsUsedObserver::class);
    }
}
