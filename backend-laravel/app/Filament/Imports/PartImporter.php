<?php

namespace App\Filament\Imports;

use App\Models\Part;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class PartImporter extends Importer
{
    protected static ?string $model = Part::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('code')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('description')
                ->rules(['max:500']),
            ImportColumn::make('quantity_in_stock')
                ->numeric()
                ->rules(['integer', 'min:0'])
                ->default(0),
            ImportColumn::make('min_stock_level')
                ->numeric()
                ->rules(['integer', 'min:0'])
                ->default(0),
            ImportColumn::make('unit_price')
                ->numeric()
                ->rules(['numeric', 'min:0'])
                ->default(0),
            ImportColumn::make('supplier')
                ->rules(['max:255']),
            ImportColumn::make('location')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): ?Part
    {
        return Part::firstOrNew([
            'code' => $this->data['code'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Importación de repuestos completada. ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' fallidas.';
        }

        return $body;
    }
}
