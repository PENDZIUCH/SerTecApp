<?php

namespace Database\Seeders;

use App\Models\LookupValue;
use Illuminate\Database\Seeder;

// Idempotente (mismo patron que SeedCustomerTypesSeeder): updateOrCreate
// por (category, value), solo toca label/sort_order - nunca pisa is_active,
// asi si un admin desactiva un destinatario desde el panel, volver a correr
// este seeder no lo reactiva solo.
//
// category = 'email_notification_recipients'. Controla quien recibe el
// email cuando un tecnico completa un parte (ver TechnicianController::
// saveParte). Arrancan los 3 activos (cliente, supervisor, tecnico) porque
// el pedido original era que supervisor/tecnico reciban una copia de
// backup - a largo plazo, cuando el sistema reemplace a los emails como
// fuente de verdad, un admin/supervisor puede desactivar supervisor y/o
// tecnico desde Filament (Administración > Listas configurables) sin tocar
// codigo.
class SeedEmailNotificationRecipientsSeeder extends Seeder
{
    public function run(): void
    {
        $destinatarios = [
            ['value' => 'cliente', 'label' => 'Cliente', 'sort_order' => 10],
            ['value' => 'supervisor', 'label' => 'Supervisor', 'sort_order' => 20],
            ['value' => 'tecnico', 'label' => 'Técnico', 'sort_order' => 30],
        ];

        foreach ($destinatarios as $destinatario) {
            LookupValue::updateOrCreate(
                ['category' => 'email_notification_recipients', 'value' => $destinatario['value']],
                ['label' => $destinatario['label'], 'sort_order' => $destinatario['sort_order']]
            );
        }
    }
}
