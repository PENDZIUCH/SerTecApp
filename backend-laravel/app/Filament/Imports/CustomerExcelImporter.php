<?php

namespace App\Filament\Imports;

use App\Models\Customer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CustomerExcelImporter extends Importer
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('business_name')
                ->label('Cliente')
                ->requiredMapping()
                ->rules(['max:255']),
            
            ImportColumn::make('address')
                ->label('Dirección')
                ->rules(['nullable', 'max:500']),
            
            ImportColumn::make('first_name')
                ->label('Contacto')
                ->rules(['nullable', 'max:100']),
            
            ImportColumn::make('phone')
                ->label('Nº de celular')
                ->rules(['nullable', 'max:50']),
            
            ImportColumn::make('email')
                ->label('Mail')
                ->rules(['nullable', 'email', 'max:255']),
            
            ImportColumn::make('notes')
                ->label('Observaciones')
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): ?Customer
    {
        // Crear nuevo cliente o actualizar si existe por email
        if (!empty($this->data['email'])) {
            return Customer::firstOrNew([
                'email' => $this->data['email'],
            ]);
        }
        
        // Si no hay email, crear siempre nuevo
        return new Customer();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Importación de clientes completada. ' . number_format($import->successful_rows) . ' ' . str('fila')->plural($import->successful_rows) . ' importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('fila')->plural($failedRowsCount) . ' fallaron.';
        }

        return $body;
    }
}
