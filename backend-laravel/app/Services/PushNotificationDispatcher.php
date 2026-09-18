<?php

namespace App\Services;

use App\Models\LookupValue;
use App\Notifications\PushNotification;
use Illuminate\Support\Facades\Log;

// Punto unico de entrada para mandar un Web Push - centraliza el chequeo
// de "esta este evento activo" (lookup_values, category=
// 'push_notification_events') para no repetirlo en cada uno de los 4
// call sites (TechnicianController::saveParte, WorkPartResource
// aprobar/rechazar, WorkOrderNotifier). Mismo criterio que ya usa
// TechnicianController::saveParte con LookupValue::optionsFor(
// 'email_notification_recipients') para los emails - si el evento esta
// desactivado no se manda el push, pero la notificacion in-app
// (Filament\Notifications\Notification::make()->sendToDatabase()) sigue
// funcionando igual, eso no se apaga nunca desde aca.
class PushNotificationDispatcher
{
    /**
     * @param  \App\Models\User|iterable<\App\Models\User>|null  $notifiable
     */
    public static function send(
        string $event,
        $notifiable,
        string $title,
        string $body,
        ?string $url = null,
        ?string $icon = null,
    ): void {
        if (! $notifiable) {
            return;
        }

        $eventosActivos = LookupValue::optionsFor('push_notification_events');

        if (! array_key_exists($event, $eventosActivos)) {
            return;
        }

        $notification = new PushNotification($title, $body, $url, $icon);

        $recipients = is_iterable($notifiable) ? $notifiable : [$notifiable];

        foreach ($recipients as $recipient) {
            if (! $recipient) {
                continue;
            }

            try {
                $recipient->notify($notification);
            } catch (\Exception $e) {
                Log::warning("Error enviando push notification ({$event}) a user #{$recipient->id}: " . $e->getMessage());
            }
        }
    }
}
