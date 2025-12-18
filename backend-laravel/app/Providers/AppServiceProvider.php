<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
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
        // Forzar HTTPS cuando la app está detrás de un proxy (Cloudflare Tunnel)
        if ($this->app->environment('production') || request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }
        
        WoPartUsed::observe(WoPartsUsedObserver::class);
    }
}
