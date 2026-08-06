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

        // Cargar configuración de email desde la DB
        $this->configureMailFromDb();
    }

    private function configureMailFromDb(): void
    {
        try {
            $host = \App\Models\SystemSetting::get('mail_host');
            if ($host) {
                config([
                    'mail.mailer'                  => 'smtp',
                    'mail.mailers.smtp.host'       => $host,
                    'mail.mailers.smtp.port'       => \App\Models\SystemSetting::get('mail_port', 465),
                    'mail.mailers.smtp.username'   => \App\Models\SystemSetting::get('mail_username'),
                    'mail.mailers.smtp.password'   => \App\Models\SystemSetting::get('mail_password'),
                    'mail.mailers.smtp.encryption' => \App\Models\SystemSetting::get('mail_encryption', 'ssl'),
                    'mail.from.address'            => \App\Models\SystemSetting::get('mail_from_address'),
                    'mail.from.name'               => \App\Models\SystemSetting::get('mail_from_name', 'SerTecApp'),
                ]);
            }
        } catch (\Exception $e) {
            // Silencioso — la tabla puede no existir en migraciones iniciales
        }
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
