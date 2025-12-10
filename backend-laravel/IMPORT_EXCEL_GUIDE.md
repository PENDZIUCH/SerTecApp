# GUÍA DEFINITIVA: IMPORT EXCEL/CSV EN FILAMENT

## ✅ SOLUCIÓN QUE FUNCIONA (PROBADA)

### 🚫 LO QUE NO FUNCIONA
- ❌ Filament `ImportAction` + `Importer` → Solo CSV, no Excel
- ❌ `acceptedFileTypes()` en ImportAction → No existe
- ❌ Confiar en que el navegador muestre todos los archivos
- ❌ `->native()` en FileUpload → No existe en esta versión

### ✅ LO QUE SÍ FUNCIONA

**PATRÓN PROBADO:**
```php
Actions\Action::make('import')
    ->label('Importar Excel/CSV')
    ->icon('heroicon-o-arrow-up-tray')
    ->color('success')
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
                if (empty(array_filter($row))) continue;
                
                $rowData = array_combine($headers, $row);
                
                try {
                    $data = [
                        'field1' => $this->getColumnValue($rowData, ['nombre1', 'name1', 'col1']),
                        'field2' => $this->getColumnValue($rowData, ['nombre2', 'name2', 'col2']),
                        // ... más campos
                    ];
                    
                    if (empty($data['field1'])) {
                        $errors++;
                        continue;
                    }
                    
                    Model::create($data);
                    $imported++;
                    
                } catch (\Exception $e) {
                    $errors++;
                }
            }
            
            Storage::disk('local')->delete($data['file']);
            
            Notification::make()
                ->title('Importación completada')
                ->body("✅ {$imported} registro(s) importado(s)" . ($errors > 0 ? " | ❌ {$errors} error(es)" : ''))
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
```

### 📋 HELPER NECESARIO
```php
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
```

## 🔧 REQUISITOS TÉCNICOS

### 1. Composer Package
```bash
composer require maatwebsite/excel
```

### 2. PHP Extension (CRÍTICO)
```ini
# C:\php\php.ini (línea 974)
extension=zip  # SIN punto y coma
```

Verificar:
```bash
php -m | grep zip
```

### 3. Config Publicada
```bash
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
```

### 4. Imports en la clase
```php
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
```

## 📊 MAPEO DE COLUMNAS

### CUSTOMERS (Implementado)
```php
'business_name' => ['cliente', 'razon social', 'nombre', 'empresa']
'address' => ['direccion', 'domicilio', 'address']
'first_name' => ['contacto', 'nombre contacto', 'persona']
'phone' => ['telefono', 'celular', 'nro de celular', 'nro de linea', 'tel']
'email' => ['email', 'mail', 'correo']
'notes' => ['observaciones', 'notas', 'obs']
```

### EQUIPMENT (Pendiente)
```php
'serial_number' => ['numero de serie', 'serial', 'serie', 'ns']
'brand' => ['marca', 'brand', 'fabricante']
'model' => ['modelo', 'model']
'equipment_code' => ['codigo', 'code', 'codigo equipo']
'purchase_date' => ['fecha compra', 'fecha adquisicion', 'purchase date']
'location' => ['ubicacion', 'location', 'lugar']
'status' => ['estado', 'status', 'condicion']
```

### PARTS (Pendiente)
```php
'name' => ['nombre', 'name', 'repuesto', 'parte']
'sku' => ['sku', 'codigo', 'code', 'referencia']
'description' => ['descripcion', 'description', 'detalle']
'unit_cost' => ['precio', 'costo', 'cost', 'price', 'valor']
'stock_qty' => ['stock', 'cantidad', 'qty', 'existencia']
'min_stock_level' => ['minimo', 'stock minimo', 'min stock']
```

## 🎯 VALORES POR DEFECTO

Siempre agregar defaults:
```php
$data['customer_type'] = $data['customer_type'] ?? 'company';
$data['is_active'] = $data['is_active'] ?? true;
$data['country'] = $data['country'] ?? 'Argentina';
```

## 🔍 MANEJO DE DUPLICADOS

```php
// Por email
if (!empty($data['email'])) {
    $existing = Model::where('email', $data['email'])->first();
    if ($existing) {
        $existing->update($data);
        $imported++;
        continue;
    }
}

// Por otro campo único
if (!empty($data['sku'])) {
    $existing = Part::where('sku', $data['sku'])->first();
    if ($existing) {
        $existing->update($data);
        $imported++;
        continue;
    }
}
```

## 🚀 PRODUCCIÓN

En servidores (Hostinger, etc):
- ✅ `zip` extension viene habilitada por defecto
- ✅ Maatwebsite/Excel funciona sin config adicional
- ✅ Solo agregar en composer.json

## 📝 CHECKLIST RÁPIDO

- [ ] `composer require maatwebsite/excel`
- [ ] Verificar `extension=zip` en php.ini
- [ ] Publicar config Excel
- [ ] Crear Action con FileUpload
- [ ] Implementar helper getColumnValue()
- [ ] Mapear columnas en español
- [ ] Agregar defaults
- [ ] Manejo de duplicados
- [ ] Limpieza de archivos temp
- [ ] Notificaciones con contador
- [ ] Probar con archivo real

## 🎓 LECCIONES APRENDIDAS

1. **Filament ImportAction es trampa**: Solo funciona bien con CSV
2. **Action custom es la solución**: Control total del proceso
3. **Maatwebsite directo**: Sin intermediarios, sin problemas
4. **ZIP es obligatorio**: Para .xlsx (no para .csv)
5. **Auto-mapeo flexible**: Array de nombres posibles por columna
6. **Always use absolute paths**: Storage::disk('local')->path()
7. **Skip empty rows**: `array_filter($row)` antes de procesar
8. **Clean temp files**: `Storage::delete()` después de import
9. **User feedback**: Notificaciones con contador de éxitos/errores
10. **Git commit temprano**: Antes de romper cosas

## 🔄 REPLICAR EN OTROS MODELS

1. Copiar estructura de Action de ListCustomers.php
2. Cambiar Model (Customer → Equipment/Part)
3. Actualizar mapeo de columnas
4. Ajustar campos requeridos
5. Configurar detección de duplicados
6. Probar con archivo real
7. Commit con mensaje descriptivo

---
**Archivo de referencia**: `ListCustomers.php` líneas 14-141
**Commit**: a7627cb
**Fecha**: 2024-12-09
**Status**: ✅ FUNCIONANDO EN PRODUCCIÓN
