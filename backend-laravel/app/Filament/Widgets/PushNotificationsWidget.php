<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

// Botón simple de opt-in a Web Push para supervisores/admins - mismo
// criterio que el lado PWA (sertecapp-tecnicos/hooks/usePushNotifications.ts):
// no se activa solo, el usuario lo toca cuando quiere. A diferencia de la
// PWA, Filament corre bajo el guard de sesion 'web' (no Sanctum), pero el
// endpoint POST/DELETE /api/v1/push-subscriptions esta protegido con
// auth:sanctum - para poder pegarle a ese mismo endpoint desde el
// navegador de este panel (sin duplicar el controller ni la lógica de
// guardado), este widget emite un Sanctum token de un solo uso, scoped
// a esta sesion (ver getPushTokenProperty).
class PushNotificationsWidget extends Widget
{
    protected static string $view = 'filament.widgets.push-notifications';

    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['administrador', 'supervisor', 'super_admin']);
    }

    /**
     * Token Sanctum de un solo uso para que el JS de este widget pueda
     * autenticar su request a /api/v1/push-subscriptions (el mismo
     * endpoint que usa la PWA de técnicos). Se borra cualquier token viejo
     * con el mismo nombre antes de crear uno nuevo, así no se acumulan
     * tokens huérfanos en cada visita al dashboard - mismo espíritu que
     * MagicLinkController (tokens de acceso descartables, no de larga
     * vida). El plaintext solo llega al navegador del propio usuario
     * dueño del token, en una pagina que ya requiere estar logueado.
     */
    public function getPushTokenProperty(): string
    {
        $user = auth()->user();
        $user->tokens()->where('name', 'filament-push-widget')->delete();

        return $user->createToken('filament-push-widget')->plainTextToken;
    }

    public function getVapidPublicKeyProperty(): ?string
    {
        return config('webpush.vapid.public_key');
    }
}
