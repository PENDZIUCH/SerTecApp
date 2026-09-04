<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
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
        // super_admin pasa cualquier chequeo de autorizacion sin depender de
        // que tenga los 226 permisos Shield bien sincronizados a mano.
        Gate::before(fn ($user) => $user->hasRole('super_admin') ? true : null);

        // Auto-detectar entorno y forzar esquema correcto
        $this->configureUrlScheme();
        
        WoPartUsed::observe(WoPartsUsedObserver::class);

        // Cargar configuración de email desde la DB
        $this->configureMailFromDb();

        // Cargar APP_NAME desde la DB
        try {
            $appName = \App\Models\SystemSetting::get('app_name');
            if ($appName) {
                config(['app.name' => $appName]);
            }
        } catch (\Exception $e) {}
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
