<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

// Notificacion generica de Web Push, compartida por los 4 eventos
// configurables (parte_rechazado, parte_aprobado,
// parte_pendiente_aprobacion, orden_nueva_asignada - ver
// App\Services\PushNotificationDispatcher, que es quien decide SI se manda
// segun el toggle en lookup_values). No hace falta una clase por evento:
// el contenido (titulo/cuerpo/url) ya viene armado por el caller, esta
// clase solo lo empaqueta para el canal WebPushChannel del paquete
// laravel-notification-channels/webpush.
class PushNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $body,
        private readonly ?string $url = null,
        private readonly ?string $icon = null,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        // Sin icono default hardcodeado: PWA (tecnicos) y Filament (admin)
        // son dos origenes distintos con sets de assets distintos - cada
        // caller pasa la URL absoluta que le corresponda si le importa, y
        // si no se pasa nada el navegador usa el icono default del sistema.
        $message = (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->options(['TTL' => 300]);

        if ($this->icon) {
            $message->icon($this->icon);
        }

        if ($this->url) {
            $message->data(['url' => $this->url]);
        }

        return $message;
    }
}
