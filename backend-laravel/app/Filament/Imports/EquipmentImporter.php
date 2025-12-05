<?php

namespace App\Filament\Imports;

use App\Models\Equipment;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class EquipmentImporter extends Importer
{
    protected static ?string $model = Equipment::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('serial_number')
                ->label('Serial Number')
                ->rules(['max:255']),
            ImportColumn::make('equipment_brand_id')
                ->label('Brand ID')
                ->numeric()
                ->rules(['exists:equipment_brands,id']),
            ImportColumn::make('equipment_model_id')
                ->label('Model ID')
                ->numeric()
                ->rules(['exists:equipment_models,id']),
            ImportColumn::make('customer_id')
                ->label('Customer ID')
                ->numeric()
                ->rules(['exists:customers,id']),
            ImportColumn::make('installation_date')
                ->rules(['date']),
            ImportColumn::make('status')
                ->rules(['in:operational,under_repair,decommissioned'])
                ->default('operational'),
            ImportColumn::make('location')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): ?Equipment
    {
        return Equipment::firstOrNew([
            'serial_number' => $this->data['serial_number'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Importación de equipos completada. ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' fallidas.';
        }

        return $body;
    }
}
