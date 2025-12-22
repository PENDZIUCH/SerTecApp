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
        // Auto-detectar entorno y forzar esquema correcto
        $this->configureUrlScheme();
        
        WoPartUsed::observe(WoPartsUsedObserver::class);
    }
    
    private function configureUrlScheme(): void
    {
        $host = request()->getHost();
        
        // Si acceden por el dominio del tunnel o está en producción, forzar HTTPS
        if (
            str_contains($host, 'pendziuch.com') || 
            $this->app->environment('production') ||
            request()->header('X-Forwarded-Proto') === 'https'
        ) {
            URL::forceScheme('https');
            
            // Actualizar APP_URL dinámicamente si es necesario
            if (str_contains($host, 'pendziuch.com')) {
                config(['app.url' => 'https://' . $host]);
            }
        }
    }
}
