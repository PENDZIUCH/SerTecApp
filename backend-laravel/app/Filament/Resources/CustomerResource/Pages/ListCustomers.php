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

    protected static ?string $recordTitleAttribute = 'business_name';
    
    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->latest('created_at');
    }

    protected function getHeaderActions(): array
    {
        return [
            // BOTÓN PRINCIPAL: Crear Cliente (VERDE - seguro para todos)
            Actions\CreateAction::make()
                ->color('success'),
            
            // BOTÓN EXPORT: Backup/Exportar (AZUL - solo admin)
            Actions\Action::make('export')
                ->label('Exportar Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->visible(fn () => auth()->user()->hasRole('admin'))
                ->action(function () {
                    $fileName = 'clientes_' . now()->format('Y-m-d_His') . '.xlsx';
                    
                    return Excel::download(new class implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                        public function collection()
                        {
                            return Customer::all()->map(function ($customer) {
                                return [
                                    'ID' => $customer->id,
                                    'Tipo' => $customer->customer_type,
                                    'Razón Social' => $customer->business_name,
                                    'Nombre' => $customer->first_name,
                                    'Apellido' => $customer->last_name,
                                    'Email' => $customer->email,
                                    'Email Secundario' => $customer->secondary_email,
                                    'Teléfono' => $customer->phone,
                                    'CUIT/CUIL' => $customer->tax_id,
                                    'Dirección' => $customer->address,
                                    'Ciudad' => $customer->city,
                                    'Provincia' => $customer->state,
                                    'País' => $customer->country,
                                    'Código Postal' => $customer->postal_code,
                                    'Activo' => $customer->is_active ? 'Sí' : 'No',
                                    'Creado' => $customer->created_at?->format('Y-m-d H:i'),
                                    'Actualizado' => $customer->updated_at?->format('Y-m-d H:i'),
                                ];
                            });
                        }
                        
                        public function headings(): array
                        {
                            return [
                                'ID', 'Tipo', 'Razón Social', 'Nombre', 'Apellido',
                                'Email', 'Email Secundario', 'Teléfono', 'CUIT/CUIL',
                                'Dirección', 'Ciudad', 'Provincia', 'País', 'Código Postal',
                                'Activo', 'Creado', 'Actualizado'
                            ];
                        }
                    }, $fileName);
                }),
            
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
                                ->persistent() // NO se cierra automáticamente
                                ->send();
                            return;
                        }
                        
                        // Primera fila = headers
                        $headers = array_map('strtolower', array_map('trim', $rows[0]));
                        unset($rows[0]);
                        
                        $imported = 0;
                        $errors = 0;
                        $warnings = 0; // Registros con campos críticos vacíos
                        
                        foreach ($rows as $row) {
                            if (empty(array_filter($row))) continue; // Skip empty rows
                            
                            $rowData = array_combine($headers, $row);
                            
                            try {
                                // Get raw values
                                $rawBusinessName = $this->getColumnValue($rowData, ['cliente', 'razon social', 'razón social', 'nombre', 'empresa']);
                                $rawContactName = $this->getColumnValue($rowData, ['contacto', 'nombre contacto', 'persona', 'nombre']);
                                $rawAddress = $this->getColumnValue($rowData, ['direccion', 'dirección', 'domicilio', 'address', 'dir']);
                                $rawEmail = $this->getColumnValue($rowData, ['mail', 'correo electronico', 'email', 'correo', 'e-mail']);
                                $rawSecondaryEmail = $this->getColumnValue($rowData, ['email secundario', 'mail secundario', 'segundo email', 'secondary email']);
                                
                                // SMART EMAIL PARSER: Separar múltiples emails
                                $email = null;
                                $secondaryEmail = null;
                                $additionalEmailsNote = null;
                                
                                if ($rawEmail) {
                                    $parsedEmails = $this->parseMultipleEmails($rawEmail);
                                    $email = $parsedEmails['primary'];
                                    // Si hay rawSecondaryEmail explícito, usarlo
                                    $secondaryEmail = $rawSecondaryEmail ?: $parsedEmails['secondary'];
                                    $additionalEmailsNote = $parsedEmails['additional'];
                                } elseif ($rawSecondaryEmail) {
                                    // Si solo hay secondary email, ponerlo como primary
                                    $email = $rawSecondaryEmail;
                                }
                                
                                // SMART PARSING: Split contact name into first/last
                                $firstName = null;
                                $lastName = null;
                                if ($rawContactName) {
                                    $nameParts = explode(' ', trim($rawContactName));
                                    if (count($nameParts) >= 2) {
                                        // Si tiene 2+ palabras: primera = nombre, resto = apellido
                                        $firstName = $nameParts[0];
                                        $lastName = implode(' ', array_slice($nameParts, 1));
                                    } else {
                                        // Si tiene 1 palabra: todo en first_name
                                        $firstName = $rawContactName;
                                    }
                                }
                                
                                // SMART PARSING: Extract city from address
                                $address = $rawAddress;
                                $city = null;
                                if ($rawAddress) {
                                    // Buscar última palabra después de coma, slash o guión
                                    if (preg_match('/[,\/\-]\s*([A-Za-zÀ-ÿ\s]+)$/u', $rawAddress, $matches)) {
                                        $possibleCity = trim($matches[1]);
                                        // Si tiene menos de 30 caracteres, probablemente es ciudad
                                        if (strlen($possibleCity) < 30 && !preg_match('/\d/', $possibleCity)) {
                                            $city = $possibleCity;
                                            // Remover ciudad de la dirección
                                            $address = trim(preg_replace('/[,\/\-]\s*' . preg_quote($possibleCity, '/') . '$/u', '', $rawAddress));
                                        }
                                    }
                                }
                                
                                // Map columns - NOMBRES EXACTOS DEL EXCEL
                                $customerData = [
                                    'customer_type' => 'company',
                                    'business_name' => $rawBusinessName,
                                    'first_name' => $firstName,
                                    'last_name' => $lastName,
                                    'address' => $address,
                                    'city' => $city,
                                    'phone' => $this->getColumnValue($rowData, ['movil', 'telefono 1', 'nº de celular', 'nº de linea', 'no de celular', 'no de linea', 'telefono', 'celular', 'tel']),
                                    'email' => $email,
                                    'secondary_email' => $secondaryEmail,
                                    'tax_id' => $this->parseTaxId($this->getColumnValue($rowData, ['nro. de documento', 'nro de documento', 'documento', 'cuit', 'cuil', 'cuit/cuil', 'tax id', 'id fiscal'])),
                                    'state' => null,
                                    'country' => 'Argentina',
                                    'postal_code' => null,
                                    'notes' => $this->getColumnValue($rowData, ['observaciones', 'notas', 'obs', 'comentarios']),
                                    'is_active' => true,
                                ];
                                
                                // Agregar emails adicionales a notas si existen
                                if ($additionalEmailsNote) {
                                    $customerData['notes'] = trim(($customerData['notes'] ?? '') . "\n\n" . $additionalEmailsNote);
                                }
                                
                                // Validar campo obligatorio
                                if (empty($customerData['business_name'])) {
                                    $errors++;
                                    continue;
                                }
                                
                                // ALARMA: Detectar si faltan campos críticos
                                $missingCritical = [];
                                if (empty($customerData['phone'])) $missingCritical[] = 'teléfono';
                                if (empty($customerData['address'])) $missingCritical[] = 'dirección';
                                if (empty($customerData['email'])) $missingCritical[] = 'email';
                                
                                if (count($missingCritical) >= 2) {
                                    // Si faltan 2 o más campos críticos, es sospechoso
                                    $warnings++;
                                }
                                
                                // Limpiar email vacío
                                if (empty($customerData['email']) || in_array($customerData['email'], ['-', 'N/A', 'n/a', ''])) {
                                    $customerData['email'] = null;
                                }
                                
                                // Buscar duplicado por email (incluyendo soft deleted)
                                if (!empty($customerData['email'])) {
                                    $existing = Customer::withTrashed()
                                        ->where('email', $customerData['email'])
                                        ->first();
                                    
                                    if ($existing) {
                                        if ($existing->trashed()) {
                                            // Si estaba eliminado, restaurar y actualizar
                                            $existing->restore();
                                        }
                                        $existing->update($customerData);
                                        $imported++;
                                        continue;
                                    }
                                }
                                
                                // Buscar duplicado por business_name (gimnasios sin email único)
                                $existing = Customer::withTrashed()
                                    ->where('business_name', $customerData['business_name'])
                                    ->first();
                                
                                if ($existing) {
                                    if ($existing->trashed()) {
                                        $existing->restore();
                                    }
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
                        
                        // Notificación con alarmas
                        $message = "✅ {$imported} cliente(s) importado(s)";
                        if ($errors > 0) $message .= " | ❌ {$errors} error(es)";
                        if ($warnings > 0) $message .= " | ⚠️ {$warnings} con datos incompletos";
                        
                        $notificationType = 'success';
                        if ($warnings > ($imported * 0.3)) {
                            // Si más del 30% tiene datos incompletos, es warning
                            $notificationType = 'warning';
                            $message .= "\n\n⚠️ ALERTA: Muchos registros sin teléfono/dirección/email. Verificar formato del Excel.";
                        }
                        
                        Notification::make()
                            ->title('Importación completada')
                            ->body($message)
                            ->{$notificationType}()
                            ->persistent() // NO se cierra automáticamente
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error en la importación')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent() // NO se cierra automáticamente
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
                ->modalDescription('PELIGRO: Esta acción NO se puede deshacer. Se eliminarán PERMANENTEMENTE todos los clientes de la base de datos (incluyendo borrados). Solo usar en caso de reset completo.')
                ->modalSubmitActionLabel('Sí, eliminar TODOS')
                ->action(function () {
                    $count = Customer::withTrashed()->count();
                    Customer::withTrashed()->forceDelete(); // Force delete = elimina físicamente
                    Notification::make()
                        ->title('Clientes eliminados')
                        ->body("Se eliminaron permanentemente {$count} cliente(s)")
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
    
    /**
     * SMART EMAIL PARSER
     * Separa múltiples emails en primary, secondary y additional (notes)
     * 
     * @param string $rawEmail - Email(s) raw con separadores
     * @return array - ['primary' => string, 'secondary' => string, 'additional' => string]
     */
    private function parseMultipleEmails(string $rawEmail): array
    {
        // Separadores comunes: / , ; espacio
        $emails = preg_split('/[\/,;\s]+/', $rawEmail);
        
        // Filtrar y validar
        $validEmails = [];
        foreach ($emails as $email) {
            $email = trim($email);
            // Validación básica de email
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validEmails[] = $email;
            }
        }
        
        $result = [
            'primary' => $validEmails[0] ?? null,
            'secondary' => $validEmails[1] ?? null,
            'additional' => null,
        ];
        
        // Si hay más de 2 emails, el resto va a notas
        if (count($validEmails) > 2) {
            $additionalEmails = array_slice($validEmails, 2);
            $result['additional'] = "Emails adicionales:\n" . implode("\n", $additionalEmails);
        }
        
        return $result;
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
    
    /**
     * SMART TAX ID PARSER (CUIT/CUIL)
     * Valida y formatea CUIT/CUIL argentino
     * 
     * @param string|null $taxId - CUIT/CUIL raw
     * @return string|null - CUIT/CUIL formateado o null si inválido
     */
    private function parseTaxId(?string $taxId): ?string
    {
        if (!$taxId) {
            return null;
        }
        
        // Remover todo excepto números
        $clean = preg_replace('/[^0-9]/', '', $taxId);
        
        // Validar longitud (debe tener 11 dígitos)
        if (strlen($clean) !== 11) {
            return null;
        }
        
        // Validar dígito verificador
        if (!$this->validateCuitCheckDigit($clean)) {
            // Si es inválido, devolver formateado pero sin validar
            // (puede ser error en origen)
            return substr($clean, 0, 2) . '-' . substr($clean, 2, 8) . '-' . substr($clean, 10, 1);
        }
        
        // Formatear: XX-XXXXXXXX-X
        return substr($clean, 0, 2) . '-' . substr($clean, 2, 8) . '-' . substr($clean, 10, 1);
    }
    
    /**
     * Validar dígito verificador de CUIT/CUIL argentino
     * Algoritmo: módulo 11
     */
    private function validateCuitCheckDigit(string $cuit): bool
    {
        if (strlen($cuit) !== 11) {
            return false;
        }
        
        $multipliers = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        
        for ($i = 0; $i < 10; $i++) {
            $sum += (int)$cuit[$i] * $multipliers[$i];
        }
        
        $mod = $sum % 11;
        $checkDigit = $mod === 0 ? 0 : ($mod === 1 ? 9 : 11 - $mod);
        
        return (int)$cuit[10] === $checkDigit;
    }
}
