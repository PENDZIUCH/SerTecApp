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
            // BOTÓN PRINCIPAL: Crear Cliente (VERDE - seguro para todos)
            Actions\CreateAction::make()
                ->color('success'),
            
            // BOTÓN SECUNDARIO: Importar (AMARILLO - solo admin)
            Actions\Action::make('import')
                ->label('Importar Excel/CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->visible(fn () => auth()->user()->hasRole('admin'))
                ->form([
                    FileUpload::make('file')
                        ->label('Archivo Excel o CSV')
                        ->required()
                        ->maxSize(10240)
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.oasis.opendocument.spreadsheet',
                            'text/csv',
                            'text/plain',
                            'application/csv',
                        ])
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
                                // Map columns - NOMBRES EXACTOS DEL EXCEL
                                $customerData = [
                                    'customer_type' => 'company',
                                    'business_name' => $this->getColumnValue($rowData, ['cliente', 'razon social', 'nombre', 'empresa']),
                                    'first_name' => $this->getColumnValue($rowData, ['contacto', 'nombre contacto', 'persona']),
                                    'last_name' => null,
                                    'address' => $this->getColumnValue($rowData, ['direccion', 'domicilio', 'address', 'dir']),
                                    'phone' => $this->getColumnValue($rowData, ['nº de celular', 'nº de linea', 'no de celular', 'no de linea', 'telefono', 'celular', 'tel']),
                                    'email' => $this->getColumnValue($rowData, ['mail', 'email', 'correo', 'e-mail']),
                                    'tax_id' => null,
                                    'city' => null,
                                    'state' => null,
                                    'country' => 'Argentina',
                                    'postal_code' => null,
                                    'notes' => $this->getColumnValue($rowData, ['observaciones', 'notas', 'obs', 'comentarios']),
                                    'is_active' => true,
                                ];
                                
                                // Validar que al menos tenga business_name
                                if (empty($customerData['business_name'])) {
                                    $errors++;
                                    continue;
                                }
                                
                                // Limpiar email vacío
                                if (empty($customerData['email']) || in_array($customerData['email'], ['-', 'N/A', 'n/a', ''])) {
                                    $customerData['email'] = null;
                                }
                                
                                // Buscar duplicado por email
                                if (!empty($customerData['email'])) {
                                    $existing = Customer::where('email', $customerData['email'])->first();
                                    if ($existing) {
                                        $existing->update($customerData);
                                        $imported++;
                                        continue;
                                    }
                                }
                                
                                // Buscar duplicado por business_name (gimnasios suelen no tener email único)
                                $existing = Customer::where('business_name', $customerData['business_name'])->first();
                                if ($existing) {
                                    $existing->update($customerData);
                                    $imported++;
                                    continue;
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
            
            // BOTÓN PELIGROSO: Eliminar Todos (ROJO - solo admin)
            Actions\Action::make('deleteAll')
                ->label('Eliminar Todos')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn () => auth()->user()->hasRole('admin'))
                ->requiresConfirmation()
                ->modalHeading('⚠️ ¿Eliminar TODOS los clientes?')
                ->modalDescription('PELIGRO: Esta acción NO se puede deshacer. Se eliminarán PERMANENTEMENTE todos los clientes de la base de datos. Solo usar en caso de reset completo.')
                ->modalSubmitActionLabel('Sí, eliminar TODOS')
                ->action(function () {
                    $count = Customer::count();
                    Customer::query()->delete();
                    Notification::make()
                        ->title('Clientes eliminados')
                        ->body("Se eliminaron {$count} cliente(s)")
                        ->warning()
                        ->send();
                }),
        ];
    }
    
    private function getColumnValue(array $row, array $possibleNames): ?string
    {
        foreach ($possibleNames as $name) {
            // Normalizar: quitar acentos, convertir a minúsculas, quitar espacios extra
            $normalizedName = $this->normalizeString($name);
            
            foreach ($row as $key => $value) {
                $normalizedKey = $this->normalizeString($key);
                
                if ($normalizedKey === $normalizedName && !empty(trim($value))) {
                    return trim($value);
                }
            }
        }
        return null;
    }
    
    private function normalizeString(string $str): string
    {
        // Convertir a minúsculas
        $str = mb_strtolower($str, 'UTF-8');
        
        // Quitar acentos
        $str = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü', 'º', 'ª'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u', 'o', 'a'],
            $str
        );
        
        // Quitar espacios extra y caracteres especiales
        $str = preg_replace('/\s+/', ' ', $str);
        $str = trim($str);
        
        return $str;
    }
}
