# 🦄 SERTECAPP - ARQUITECTURA Y DECISIONES DE DISEÑO SENIOR

## 🎯 FILOSOFÍA DEL PROYECTO
**Automatización máxima + Inversión mínima + ROI máximo**
- Desarrollo: Apps/websites con WordPress, React, Next.js
- Recursos: Devs freelance + AI para acelerar
- Stack: Headless PWA SPA
- Servicios: Marca, redes, video 4K, 3D

## 📋 PENDIENTES CRÍTICOS PARA PRODUCCIÓN

### 1. CAMPOS CON TABLAS DE REFERENCIA (PRIORITY HIGH)

#### ✅ CUSTOMERS
```php
// ❌ ACTUAL: Text input libre
'state' => TextInput::make('state')->label('Provincia')

// ✅ MEJORAR: Select con provincias argentinas
'state' => Forms\Components\Select::make('state')
    ->label('Provincia')
    ->options([
        'Buenos Aires' => 'Buenos Aires',
        'CABA' => 'Ciudad Autónoma de Buenos Aires',
        'Catamarca' => 'Catamarca',
        'Chaco' => 'Chaco',
        'Chubut' => 'Chubut',
        'Córdoba' => 'Córdoba',
        'Corrientes' => 'Corrientes',
        'Entre Ríos' => 'Entre Ríos',
        'Formosa' => 'Formosa',
        'Jujuy' => 'Jujuy',
        'La Pampa' => 'La Pampa',
        'La Rioja' => 'La Rioja',
        'Mendoza' => 'Mendoza',
        'Misiones' => 'Misiones',
        'Neuquén' => 'Neuquén',
        'Río Negro' => 'Río Negro',
        'Salta' => 'Salta',
        'San Juan' => 'San Juan',
        'San Luis' => 'San Luis',
        'Santa Cruz' => 'Santa Cruz',
        'Santa Fe' => 'Santa Fe',
        'Santiago del Estero' => 'Santiago del Estero',
        'Tierra del Fuego' => 'Tierra del Fuego',
        'Tucumán' => 'Tucumán',
    ])
    ->searchable()
    ->native(false)
```

#### ✅ EQUIPMENT
```php
// CREAR TABLAS:
- brands (marcas de equipos: Life Fitness, Technogym, Matrix, etc)
- equipment_types (tipos: Cinta, Bicicleta, Remo, Elíptica, etc)
- equipment_statuses (estados: Operativo, En Reparación, Fuera de Servicio, etc)

// MIGRATIONS:
php artisan make:model Brand -m
php artisan make:model EquipmentType -m
php artisan make:model EquipmentStatus -m
```

#### ✅ PARTS (REPUESTOS)
```php
// CREAR TABLAS:
- part_categories (categorías: Motor, Banda, Rodamiento, Electrónica, etc)
- part_suppliers (proveedores)
- units (unidades: Unidad, Metro, Kilogramo, Litro, etc)

// En vez de text libre → Select con opciones
```

#### ✅ WORK ORDERS
```php
// YA IMPLEMENTADO CON ENUMS:
- priority: ['low', 'medium', 'high', 'urgent'] ✅
- status: ['pending', 'in_progress', 'completed', 'cancelled'] ✅

// MEJORAR:
- service_types (tipos de servicio: Mantenimiento, Reparación, Instalación)
- failure_types (tipos de falla: Mecánica, Eléctrica, Electrónica, Software)
```

### 2. ARQUITECTURA DE DATOS NORMALIZADA

```
┌─────────────┐
│  CUSTOMERS  │ (gimnasios/clientes)
└──────┬──────┘
       │
       ├──→ EQUIPMENTS (máquinas del gimnasio)
       │    └──→ MAINTENANCE_SCHEDULES (cronograma mantenimiento)
       │    └──→ EQUIPMENT_HISTORY (historial de cambios)
       │
       ├──→ WORK_ORDERS (órdenes de trabajo)
       │    └──→ WORK_ORDER_PARTS (repuestos usados)
       │    └──→ WORK_ORDER_TASKS (tareas realizadas)
       │    └──→ WORK_ORDER_FILES (fotos, PDFs)
       │
       ├──→ BUDGETS (presupuestos)
       │    └──→ BUDGET_ITEMS (ítems del presupuesto)
       │
       └──→ SUBSCRIPTIONS (contratos de mantenimiento)
            └──→ SUBSCRIPTION_INVOICES (facturas)

┌─────────────┐
│   PARTS     │ (repuestos en stock)
└──────┬──────┘
       │
       ├──→ STOCK_MOVEMENTS (entradas/salidas)
       ├──→ PART_SUPPLIERS (proveedores)
       └──→ PART_PRICES (historial de precios)

┌─────────────┐
│    USERS    │ (técnicos/admin)
└──────┬──────┘
       │
       ├──→ ROLES (admin, supervisor, técnico)
       ├──→ PERMISSIONS (permisos granulares)
       └──→ USER_SCHEDULES (disponibilidad)
```

### 3. INTEGRIDAD REFERENCIAL (YA IMPLEMENTADA ✅)

```php
// customers → work_orders
->onDelete('cascade')  // Si borro cliente, borro sus órdenes

// customers → equipments  
->onDelete('cascade')  // Si borro cliente, borro sus equipos

// equipments → work_orders
->onDelete('set null')  // Si borro equipo, órden queda sin equipo

// users → work_orders (assigned_tech_id)
->onDelete('set null')  // Si borro técnico, órden queda sin asignar
```

**NO HAY REGISTROS HUÉRFANOS POSIBLES** ✅

### 4. IMPORT EXCEL - LECCIONES APRENDIDAS

#### 🔴 ERRORES COMETIDOS (NO REPETIR)
1. No verificar datos en BD después de import
2. Asumir nombres de columnas sin acentos
3. No mapear todos los campos fillable
4. No probar con archivo real del cliente
5. Usar soft delete sin considerar duplicados

#### ✅ SOLUCIONES IMPLEMENTADAS
```php
// 1. Helper de normalización (OBLIGATORIO en todos los imports)
private function normalizeString(string $str): string {
    $str = mb_strtolower($str, 'UTF-8');
    $str = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü', 'º', 'ª'],
        ['a', 'e', 'i', 'o', 'u', 'n', 'u', 'o', 'a'],
        $str
    );
    return trim(preg_replace('/\s+/', ' ', $str));
}

// 2. Búsqueda de duplicados con soft deletes
$existing = Model::withTrashed()->where('email', $email)->first();
if ($existing) {
    if ($existing->trashed()) $existing->restore();
    $existing->update($data);
}

// 3. Alarma de calidad de datos
if ($warnings > ($imported * 0.3)) {
    // >30% con datos incompletos = WARNING
}

// 4. Force delete para "Eliminar Todos"
Model::withTrashed()->forceDelete();
```

#### 📊 FORMATO EXCEL ESTÁNDAR ARGENTINO
```
Cliente | Dirección | Contacto | Nº de celular | Nº de línea | Mail | Observaciones
```
**Características:**
- Acentos en headers (Dirección, not direccion)
- Símbolos especiales (Nº, not No)
- Múltiples opciones por campo (celular O línea)

### 5. PERMISOS Y SEGURIDAD

```php
// ROLES IMPLEMENTADOS:
- admin: acceso total
- supervisor: ver todo, editar limitado
- technician: solo sus órdenes asignadas
- operator: crear/editar, no borrar

// BOTONES PELIGROSOS (solo admin):
- Importar Excel/CSV
- Eliminar Todos
- Exportar datos completos

// VISIBLE STRATEGY:
->visible(fn () => auth()->user()->hasRole('admin'))
```

### 6. UX/UI IMPROVEMENTS PENDIENTES

#### Labels descriptivos (IMPLEMENTADO ✅)
```php
TextInput::make('business_name')
    ->label('Razón Social (Cliente)')
    ->helperText('Nombre del gimnasio o empresa')

TextInput::make('first_name')
    ->label('Nombre (Contacto)')
    ->helperText('Persona de contacto')

TextInput::make('phone')
    ->label('Teléfono (Nº de celular / Nº de línea)')
```

#### Próximos:
- [ ] Tooltips en headers de tabla
- [ ] Placeholders con ejemplos
- [ ] Validación en tiempo real
- [ ] Autocompletado de direcciones (Google Places API)

### 7. NOTIFICACIONES Y FEEDBACK

```php
// IMPLEMENTADO:
✅ Importación: contador + warnings + errores
✅ Eliminación: contador de registros

// PRÓXIMO:
- [ ] Email al completar órden de trabajo
- [ ] SMS para citas programadas
- [ ] Push notifications (PWA)
- [ ] Logs de auditoría (quién hizo qué)
```

### 8. EXPORTACIÓN DE DATOS

```php
// PRÓXIMO:
- [ ] Exportar clientes a Excel (botón amarillo)
- [ ] Exportar órdenes por período
- [ ] Exportar inventario de repuestos
- [ ] Exportar para contabilidad (facturación)

// USAR: Maatwebsite/Excel (ya instalado)
// FORMATO: mismo que import (para reimportar)
```

## 📖 MANUAL DE USO (ESTRUCTURA)

### SECCIÓN 1: IMPORTACIÓN DE DATOS
**Objetivo:** Carga masiva inicial de clientes/equipos/repuestos

#### 1.1 Preparación del Excel
```
✅ Formato requerido:
- Primera fila = headers (nombres exactos)
- Columnas obligatorias: [lista]
- Columnas opcionales: [lista]
- Formato de fechas: DD/MM/YYYY
- Emails: formato válido
- Teléfonos: solo números

❌ Evitar:
- Celdas combinadas
- Múltiples valores en una celda (excepto dirección)
- Caracteres especiales raros (emojis)
- Hojas con macros

📥 Plantillas:
- descargar plantilla_clientes.xlsx
- descargar plantilla_equipos.xlsx
- descargar plantilla_repuestos.xlsx
```

#### 1.2 Proceso de importación
```
1. Hacer BACKUP antes de importar (botón verde)
2. Click "Importar Excel/CSV" (botón amarillo)
3. Seleccionar archivo
4. Esperar notificación
5. Verificar resultado:
   - ✅ Verde: todo OK
   - ⚠️ Amarillo: revisar datos incompletos
   - ❌ Rojo: error, contactar soporte

6. Si >30% con warnings → revisar Excel y reimportar
```

#### 1.3 Solución de problemas
```
PROBLEMA: "No se importó ningún registro"
SOLUCIÓN: Verificar que primera fila tenga headers correctos

PROBLEMA: "Muchos registros con datos incompletos"
SOLUCIÓN: Verificar que columnas tengan nombres correctos
         (ver plantilla de referencia)

PROBLEMA: "Error al leer archivo"
SOLUCIÓN: Guardar como .xlsx (no .xls) y reintentar
```

### SECCIÓN 2: GESTIÓN DIARIA

#### 2.1 Crear nuevo cliente
#### 2.2 Crear órden de trabajo
#### 2.3 Asignar técnico
#### 2.4 Registrar repuestos usados
#### 2.5 Completar órden (firma digital)

### SECCIÓN 3: MANTENIMIENTO PREVENTIVO

#### 3.1 Cronogramas automáticos
#### 3.2 Notificaciones
#### 3.3 Historial de equipos

### SECCIÓN 4: REPORTES Y ANÁLISIS

#### 4.1 Dashboard principal
#### 4.2 Reportes de productividad
#### 4.3 Análisis de costos
#### 4.4 Exportación para contabilidad

### SECCIÓN 5: ADMINISTRACIÓN

#### 5.1 Gestión de usuarios
#### 5.2 Permisos por rol
#### 5.3 Backup y restauración
#### 5.4 Configuración del sistema

## 💰 ESTRATEGIA DE MONETIZACIÓN

### DEMO GRATUITO (30 días)
- 10 clientes max
- 20 órdenes de trabajo
- 1 usuario admin
- Logo "Powered by SerTecApp"

### PLAN BÁSICO ($X/mes)
- 50 clientes
- Órdenes ilimitadas
- 3 usuarios
- Soporte por email

### PLAN PROFESIONAL ($Y/mes)
- Clientes ilimitados
- 10 usuarios
- Firma digital
- API access
- Soporte prioritario

### PLAN ENTERPRISE ($Z/mes)
- Todo ilimitado
- White label
- Hosting dedicado
- Soporte 24/7
- Desarrollo custom

## 🚀 ROADMAP

### FASE 1: MVP (ACTUAL)
- [x] CRUD Customers, Equipment, Parts, WorkOrders
- [x] Import Excel
- [x] Permisos por rol
- [x] Dashboard básico
- [ ] Manual de uso

### FASE 2: AUTOMATIZACIÓN
- [ ] Mantenimiento preventivo automático
- [ ] Email/SMS notifications
- [ ] Firma digital en móvil
- [ ] Geolocalización de técnicos

### FASE 3: INTELIGENCIA
- [ ] Predicción de fallas (ML)
- [ ] Optimización de rutas
- [ ] Recomendación de repuestos
- [ ] Análisis de rentabilidad

### FASE 4: INTEGRACIÓN
- [ ] Facturación electrónica (AFIP)
- [ ] E-commerce repuestos
- [ ] Portal cliente (ver sus equipos)
- [ ] App móvil nativa

## 📊 MÉTRICAS DE ÉXITO

### TÉCNICAS
- Tiempo de carga < 2 seg
- Uptime > 99.5%
- Zero data loss
- < 5 clicks por tarea común

### NEGOCIO
- Tiempo de setup cliente: < 30 min
- Adopción usuarios: > 80% en 1 semana
- Retención mensual: > 90%
- NPS: > 50

### ECONÓMICAS
- CAC (costo adquisición): < $X
- LTV (valor vida cliente): > $Y
- Churn rate: < 5%
- MRR growth: > 20% mensual

## 🛠️ STACK TÉCNICO

### BACKEND
- Laravel 11 (PHP 8.3)
- SQLite (dev) / PostgreSQL (prod)
- Filament 3 (admin panel)
- Maatwebsite/Excel (import/export)

### FRONTEND
- Livewire (Filament)
- Alpine.js
- Tailwind CSS
- React (PWA futura)

### INFRAESTRUCTURA
- Hostinger (demo)
- AWS / DigitalOcean (prod)
- Cloudflare (CDN + SSL)
- GitHub Actions (CI/CD)

### DESARROLLO
- Git (version control)
- VS Code + AI (Claude, Cursor)
- Postman (API testing)
- PHPUnit (testing)

## 🎯 PRÓXIMAS SESIONES

### SESIÓN PRÓXIMA:
1. Implementar Select de provincias
2. Crear tablas Brand, EquipmentType, PartCategory
3. Import de Equipment con normalización
4. Import de Parts con validación de stock
5. Testing completo de integridad referencial

### IMPORTANTE:
- NO gastar créditos re-analizando
- Leer ESTE archivo primero
- Commitear ANTES de cambios grandes
- Documentar TODO en tiempo real

---

**Última actualización:** 2024-12-09
**Versión:** 1.0
**Branch:** feature/excel-importer
**Commits:** 6 (todos con descripción senior)
