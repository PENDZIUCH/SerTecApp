<?php

namespace Database\Seeders;

use App\Models\LookupValue;
use Illuminate\Database\Seeder;

// Idempotente (mismo patron que SeedEmailNotificationRecipientsSeeder):
// updateOrCreate por (category, value), solo toca label/sort_order - nunca
// pisa is_active, asi si un admin/supervisor desactiva un evento desde el
// panel, volver a correr este seeder no lo reactiva solo.
//
// category = 'push_notification_events'. Controla que eventos disparan un
// Web Push (ademas de la notificacion in-app de Filament, que sigue
// funcionando siempre, este toggle no la afecta). Ver
// App\Services\PushNotificationDispatcher, TechnicianController::
// saveParte y WorkPartResource (acciones aprobar/rechazar). Arrancan los
// 4 activos por defecto (pedido explicito de Hugo: "no descontroladas,
// deben ser configurables" - activas de entrada, apagables sin tocar
// codigo desde Filament > Administracion > Listas configurables).
class SeedPushNotificationEventsSeeder extends Seeder
{
    public function run(): void
    {
        $eventos = [
            ['value' => 'parte_rechazado', 'label' => 'Parte Rechazado (al técnico)', 'sort_order' => 10],
            ['value' => 'parte_aprobado', 'label' => 'Parte Aprobado (al técnico)', 'sort_order' => 20],
            ['value' => 'parte_pendiente_aprobacion', 'label' => 'Parte Pendiente de Aprobación (a supervisores)', 'sort_order' => 30],
            ['value' => 'orden_nueva_asignada', 'label' => 'Orden Nueva Asignada (al técnico)', 'sort_order' => 40],
        ];

        foreach ($eventos as $evento) {
            LookupValue::updateOrCreate(
                ['category' => 'push_notification_events', 'value' => $evento['value']],
                ['label' => $evento['label'], 'sort_order' => $evento['sort_order']]
            );
        }
    }
}
