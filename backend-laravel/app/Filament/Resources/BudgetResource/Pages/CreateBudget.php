<?php

namespace App\Filament\Resources\BudgetResource\Pages;

use App\Filament\Resources\BudgetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBudget extends CreateRecord
{
    protected static string $resource = BudgetResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Calcular totales
        $subtotalParts = 0;
        
        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                if ($item['item_type'] === 'part') {
                    $subtotalParts += $item['total'] ?? 0;
                }
            }
        }
        
        $data['subtotal_parts'] = $subtotalParts;
        $data['subtotal_before_discount'] = $subtotalParts;
        $data['subtotal_after_discount'] = $subtotalParts;
        $data['total_amount'] = $subtotalParts;
        $data['status'] = 'draft';
        
        return $data;
    }
}
