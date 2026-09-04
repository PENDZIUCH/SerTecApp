# GUÍA DEFINITIVA: IMPORT EXCEL/CSV EN FILAMENT

## ⚠️ LECCIONES CRÍTICAS (APRENDIDAS CON DOLOR)

### 🔴 ERROR #1: NO VERIFICAR LOS DATOS IMPORTADOS
**SIEMPRE verificar qué se guardó realmente en la BD antes de dar por terminado:**
```bash
php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); \$customer = App\Models\Customer::first(); echo json_encode(\$customer->toArray(), JSON_PRETTY_PRINT);"
```

### 🔴 ERROR #2: ASUMIR NOMBRES DE COLUMNAS SIN ACENTOS
**Excel real viene con acentos y caracteres especiales:**
- ❌ Buscar: `direccion`, `telefono`, `numero`
- ✅ Real: `Dirección`, `Teléfono`, `Nº de celular`

**SOLUCIÓN OBLIGATORIA: Helper de normalización**

### 🔴 ERROR #3: NO MAPEAR TODOS LOS CAMPOS DEL MODELO
**SIEMPRE mapear TODOS los campos fillable, aunque sean `null`:**
- Si no se especifica, Laravel no los toca
- Los registros quedan incompletos
- El formulario muestra campos vacíos

### 🔴 ERROR #4: NO PROBAR CON ARCHIVO REAL DEL CLIENTE
**NUNCA asumir la estructura del Excel:**
- Pedí captura de pantalla del Excel
- Verificá nombres EXACTOS de columnas
- Probá con archivo real antes de commit

---

## ✅ SOLUCIÓN QUE FUNCIONA (PROBADA CON DATOS REALES)

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

### 📋 HELPER NECESARIO (CON NORMALIZACIÓN DE ACENTOS)
```php
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
    // Convertir a minúsculas UTF-8
    $str = mb_strtolower($str, 'UTF-8');
    
    // Quitar acentos y caracteres especiales
    $str = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü', 'º', 'ª'],
        ['a', 'e', 'i', 'o', 'u', 'n', 'u', 'o', 'a'],
        $str
    );
    
    // Normalizar espacios
    $str = preg_replace('/\s+/', ' ', $str);
    $str = trim($str);
    
    return $str;
}
```

**POR QUÉ ES CRÍTICO:**
- Excel argentino usa acentos: `Dirección`, `Teléfono`, `Nº`
- Sin normalización: `direccion` ≠ `Dirección` → campo queda NULL
- Con normalización: `direccion` == `direccion` → ✅ funciona

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

### ⚠️ REGLAS DE MAPEO (CRÍTICAS)

1. **Usar nombres REALES del Excel del cliente:**
   ```php
   // ❌ MAL: Asumir
   'phone' => ['telefono', 'tel']
   
   // ✅ BIEN: Verificar con captura
   'phone' => ['nº de celular', 'nº de linea', 'telefono', 'celular', 'tel']
   ```

2. **Incluir variaciones con/sin acentos:**
   ```php
   'address' => ['direccion', 'dirección', 'domicilio', 'address', 'dir']
   ```

3. **Poner nombres más específicos PRIMERO:**
   ```php
   // ✅ BIEN: específico primero
   'email' => ['mail', 'email', 'correo', 'e-mail']
   
   // ❌ MAL: genérico primero puede matchear mal
   'email' => ['correo', 'mail', 'email']
   ```

4. **SIEMPRE mapear TODOS los campos fillable del modelo:**
   ```php
   // Ver en Model: protected $fillable = [...]
   // Mapear CADA UNO, aunque sea null
   ```

### CUSTOMERS (Implementado y Probado ✅)
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

## 📝 CHECKLIST RÁPIDO (OBLIGATORIO)

**ANTES DE EMPEZAR:**
- [ ] Pedir captura/muestra del Excel REAL del cliente
- [ ] Verificar nombres EXACTOS de columnas (con acentos)
- [ ] Listar TODOS los campos fillable del modelo

**DURANTE IMPLEMENTACIÓN:**
- [ ] `composer require maatwebsite/excel`
- [ ] Verificar `extension=zip` en php.ini (solo local)
- [ ] Publicar config Excel
- [ ] Crear Action con FileUpload
- [ ] Implementar helpers: getColumnValue() + normalizeString()
- [ ] Mapear TODOS los campos (incluso nulls)
- [ ] Configurar detección de duplicados
- [ ] Agregar defaults obligatorios

**DESPUÉS DE CODEAR:**
- [ ] Probar con archivo REAL del cliente
- [ ] Verificar datos en BD con query directa
- [ ] Confirmar que TODOS los campos se guardaron
- [ ] Probar en formulario de edición
- [ ] Limpiar archivos temp
- [ ] Commit con mensaje descriptivo

**SEGURIDAD:**
- [ ] Import/Delete solo visible para admin
- [ ] Modal de confirmación en Delete
- [ ] Notificaciones con contador

## 🎓 LECCIONES APRENDIDAS (ACTUALIZADO)

1. **Filament ImportAction es trampa**: Solo funciona bien con CSV
2. **Action custom es la solución**: Control total del proceso
3. **Maatwebsite directo**: Sin intermediarios, sin problemas
4. **ZIP es obligatorio**: Para .xlsx (no para .csv)
5. **NORMALIZACIÓN ES CRÍTICA**: Excel argentino tiene acentos, ñ, º
6. **Verificar datos importados**: Usar query directa a BD, no confiar
7. **Mapear TODOS los campos**: Aunque sean null, especificarlos
8. **Pedir captura del Excel**: NUNCA asumir nombres de columnas
9. **Probar con archivo real**: Del cliente, no inventado
10. **Clean temp files**: Storage::delete() después de import
11. **User feedback**: Notificaciones con contador de éxitos/errores
12. **Seguridad primero**: Import/Delete solo admin
13. **Git commit temprano**: Antes de romper cosas
14. **Documentar problemas**: Para no repetirlos en Equipment/Parts
15. **Helper reutilizable**: normalizeString() copiar a todos los imports

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
