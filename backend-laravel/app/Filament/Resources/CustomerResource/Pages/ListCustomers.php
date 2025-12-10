<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Importar Excel/CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    FileUpload::make('file')
                        ->label('Archivo Excel o CSV')
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                            'text/plain',
                        ])
                        ->maxSize(10240)
                        ->required()
                        ->helperText('Formatos: .xlsx, .xls, .csv (máx 10MB)')
                        ->disk('local')
                        ->directory('imports')
                        ->visibility('private'),
                ])
                ->action(function (array $data) {
                    try {
                        $filePath = Storage::disk('local')->path($data['file']);
                        
                        // Leer el archivo con Maatwebsite/Excel
                        $rows = Excel::toArray([], $filePath)[0];
                        
                        if (empty($rows)) {
                            Notification::make()
                                ->title('Error')
                                ->body('El archivo está vacío')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Primera fila = headers
                        $headers = array_map('strtolower', array_map('trim', $rows[0]));
                        unset($rows[0]);
                        
                        $imported = 0;
                        $errors = 0;
                        
                        foreach ($rows as $row) {
                            if (empty(array_filter($row))) continue; // Skip empty rows
                            
                            $rowData = array_combine($headers, $row);
                            
                            try {
                                // Map columns
                                $customerData = [
                                    'business_name' => $this->getColumnValue($rowData, ['cliente', 'razon social', 'nombre', 'empresa']),
                                    'address' => $this->getColumnValue($rowData, ['direccion', 'domicilio', 'address']),
                                    'first_name' => $this->getColumnValue($rowData, ['contacto', 'nombre contacto', 'persona']),
                                    'phone' => $this->getColumnValue($rowData, ['telefono', 'celular', 'nro de celular', 'nro de linea', 'tel']),
                                    'email' => $this->getColumnValue($rowData, ['email', 'mail', 'correo']),
                                    'notes' => $this->getColumnValue($rowData, ['observaciones', 'notas', 'obs']),
                                    'customer_type' => 'company',
                                    'is_active' => true,
                                    'country' => 'Argentina',
                                ];
                                
                                // Validar que al menos tenga nombre
                                if (empty($customerData['business_name'])) {
                                    $errors++;
                                    continue;
                                }
                                
                                // Limpiar email vacío
                                if (empty($customerData['email']) || in_array($customerData['email'], ['-', 'N/A'])) {
                                    $customerData['email'] = null;
                                }
                                
                                // Buscar duplicado
                                if (!empty($customerData['email'])) {
                                    $existing = Customer::where('email', $customerData['email'])->first();
                                    if ($existing) {
                                        $existing->update($customerData);
                                        $imported++;
                                        continue;
                                    }
                                }
                                
                                Customer::create($customerData);
                                $imported++;
                                
                            } catch (\Exception $e) {
                                $errors++;
                            }
                        }
                        
                        // Limpiar archivo
                        Storage::disk('local')->delete($data['file']);
                        
                        // Notificación
                        Notification::make()
                            ->title('Importación completada')
                            ->body("✅ {$imported} cliente(s) importado(s)" . ($errors > 0 ? " | ❌ {$errors} error(es)" : ''))
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error en la importación')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\CreateAction::make(),
            
            Actions\Action::make('deleteAll')
                ->label('Eliminar Todos')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('¿Eliminar todos los clientes?')
                ->modalDescription('Esta acción NO se puede deshacer. Se eliminarán TODOS los clientes de la base de datos.')
                ->modalSubmitActionLabel('Sí, eliminar todos')
                ->action(function () {
                    Customer::query()->delete();
                    Notification::make()
                        ->title('Clientes eliminados')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    private function getColumnValue(array $row, array $possibleNames): ?string
    {
        foreach ($possibleNames as $name) {
            $name = strtolower(trim($name));
            if (isset($row[$name]) && !empty(trim($row[$name]))) {
                return trim($row[$name]);
            }
        }
        return null;
    }
}
