<?php

namespace App\Filament\Imports;

use App\Models\Customer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CustomerImporter extends Importer
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('email')
                ->rules(['email', 'max:255']),
            ImportColumn::make('phone')
                ->rules(['max:50']),
            ImportColumn::make('address')
                ->rules(['max:255']),
            ImportColumn::make('city')
                ->rules(['max:100']),
            ImportColumn::make('state')
                ->rules(['max:100']),
            ImportColumn::make('type')
                ->requiredMapping()
                ->rules(['required', 'in:individual,company,gym']),
            ImportColumn::make('status')
                ->rules(['in:active,inactive,suspended'])
                ->default('active'),
            ImportColumn::make('tax_id')
                ->label('CUIT/Tax ID')
                ->rules(['max:50']),
        ];
    }

    public function resolveRecord(): ?Customer
    {
        return Customer::firstOrNew([
            'email' => $this->data['email'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Importación de clientes completada. ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' fallidas.';
        }

        return $body;
    }
}
