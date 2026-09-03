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
                ->guess(['cliente', 'razon social', 'nombre', 'name', 'business_name', 'empresa', 'razon_social'])
                ->rules(['required', 'max:255']),
            
            ImportColumn::make('address')
                ->label('Dirección')
                ->guess(['direccion', 'address', 'domicilio', 'dir', 'direccion'])
                ->rules(['nullable', 'max:500']),
            
            ImportColumn::make('first_name')
                ->label('Contacto')
                ->guess(['contacto', 'nombre contacto', 'first_name', 'contact', 'persona', 'nombre_contacto'])
                ->rules(['nullable', 'max:100']),
            
            ImportColumn::make('phone')
                ->label('Teléfono')
                ->guess(['telefono', 'celular', 'phone', 'tel', 'nro', 'numero', 'nro_celular', 'nro_linea', 'nº de celular', 'nº de línea'])
                ->rules(['nullable', 'max:50']),
            
            ImportColumn::make('email')
                ->label('Email')
                ->guess(['email', 'mail', 'correo', 'e-mail', 'e_mail'])
                ->rules(['nullable', 'email:filter', 'max:255']),
            
            ImportColumn::make('notes')
                ->label('Observaciones')
                ->guess(['observaciones', 'notas', 'notes', 'comentarios', 'obs', 'observacion'])
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): ?Customer
    {
        $data = $this->data;

        // Defaults
        $data['customer_type'] = $data['customer_type'] ?? 'company';
        $data['is_active'] = $data['is_active'] ?? true;
        $data['country'] = $data['country'] ?? 'Argentina';

        // Limpiar email
        if (empty($data['email']) || in_array($data['email'], ['-', 'N/A', 'n/a', ''])) {
            $data['email'] = null;
        }

        // Buscar duplicado
        if (!empty($data['email'])) {
            $existing = Customer::where('email', $data['email'])->first();
            if ($existing) {
                $existing->update($data);
                return $existing;
            }
        }

        if (!empty($data['business_name'])) {
            $existing = Customer::where('business_name', $data['business_name'])->first();
            if ($existing) {
                $existing->update($data);
                return $existing;
            }
        }

        return Customer::create($data);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = '✅ ' . number_format($import->successful_rows) . ' cliente(s) importado(s)';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' | ❌ ' . number_format($failedRowsCount) . ' error(es)';
        }

        return $body;
    }
}
