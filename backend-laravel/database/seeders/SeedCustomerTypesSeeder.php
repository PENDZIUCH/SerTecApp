<?php

namespace Database\Seeders;

use App\Models\LookupValue;
use Illuminate\Database\Seeder;

// Idempotente (mismo patron que SyncShieldPermissionsSeeder): updateOrCreate
// por (category, value), solo toca label/sort_order - nunca pisa is_active,
// asi si un admin desactiva un tipo desde el panel, volver a correr este
// seeder no lo reactiva solo.
class SeedCustomerTypesSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['value' => 'individual', 'label' => 'Particular', 'sort_order' => 10],
            ['value' => 'company', 'label' => 'Empresa', 'sort_order' => 20],
            ['value' => 'gym', 'label' => 'Gimnasio', 'sort_order' => 30],
            ['value' => 'country', 'label' => 'Country', 'sort_order' => 40],
            ['value' => 'hotel', 'label' => 'Hotel', 'sort_order' => 50],
            ['value' => 'consorcio', 'label' => 'Consorcio', 'sort_order' => 60],
        ];

        foreach ($tipos as $tipo) {
            LookupValue::updateOrCreate(
                ['category' => 'customer_type', 'value' => $tipo['value']],
                ['label' => $tipo['label'], 'sort_order' => $tipo['sort_order']]
            );
        }
    }
}
