# 💳 Abonos - Suscripciones Mensuales

Endpoints para gestionar abonos (contratos mensuales de mantenimiento).

---

## 📋 Tabla de Contenidos

- [GET /abonos](#get-abonos) - Listar abonos
- [GET /abonos/:id](#get-abonosid) - Obtener abono
- [POST /abonos](#post-abonos) - Crear abono
- [PUT /abonos/:id](#put-abonosid) - Actualizar abono
- [DELETE /abonos/:id](#delete-abonosid) - Suspender abono
- [GET /abonos/proximos-vencer](#get-abonos-proximos-vencer) - Abonos a vencer
- [POST /abonos/:id/renovar](#post-abonosid-renovar) - Renovar abono

---

## GET /abonos

Listar abonos con filtros y paginación.

### Request

```http
GET /api/abonos?page=1&per_page=15&estado=activo&cliente_id=5
Authorization: Bearer {token}
```

### Query Parameters

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `page` | integer | Número de página (default: 1) | `page=2` |
| `per_page` | integer | Items por página (default: 15) | `per_page=20` |
| `estado` | string | Filtrar por estado | `activo`, `vencido`, `suspendido` |
| `cliente_id` | integer | Filtrar por cliente | `cliente_id=5` |

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "cliente_id": 1,
        "cliente_nombre": "Club Ateneo Gym",
        "cliente_razon_social": "Ateneo Gym S.A.",
        "frecuencia_visitas": 2,
        "frecuencia_nombre": "2 visitas mensuales",
        "color_frecuencia": "#2196F3",
        "monto": 50000.00,
        "fecha_inicio": "2025-01-01",
        "fecha_vencimiento": "2025-12-31",
        "estado": "activo",
        "observaciones": "Abono anual con descuento",
        "created_at": "2025-01-01 10:00:00",
        "updated_at": "2025-11-20 14:30:00"
      }
    ],
    "pagination": {
      "total": 45,
      "per_page": 15,
      "current_page": 1,
      "last_page": 3
    }
  }
}
```

### Códigos de Estado

- `200` - Lista obtenida exitosamente
- `401` - No autenticado

---

## GET /abonos/:id

Obtener un abono específico con información del cliente.

### Request

```http
GET /api/abonos/1
Authorization: Bearer {token}
```

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "id": 1,
    "cliente_id": 1,
    "cliente_nombre": "Club Ateneo Gym",
    "cliente_razon_social": "Ateneo Gym S.A.",
    "cliente_telefono": "011-4444-5555",
    "cliente_email": "info@ateneogym.com",
    "frecuencia_visitas": 2,
    "frecuencia_nombre": "2 visitas mensuales",
    "color_frecuencia": "#2196F3",
    "monto": 50000.00,
    "fecha_inicio": "2025-01-01",
    "fecha_vencimiento": "2025-12-31",
    "estado": "activo",
    "observaciones": "Abono anual con descuento del 10%",
    "created_at": "2025-01-01 10:00:00",
    "updated_at": "2025-11-20 14:30:00"
  }
}
```

### Response 404 - No Encontrado

```json
{
  "success": false,
  "message": "Abono no encontrado"
}
```

---

## POST /abonos

Crear nuevo abono para un cliente.

### Request

```http
POST /api/abonos
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "cliente_id": 5,
  "frecuencia_visitas": 3,
  "monto": 75000.00,
  "fecha_inicio": "2025-12-01",
  "fecha_vencimiento": "2026-11-30",
  "estado": "activo",
  "observaciones": "Abono premium con 3 visitas mensuales"
}
```

### Validaciones

| Campo | Reglas | Descripción |
|-------|--------|-------------|
| `cliente_id` | required, integer, exists | ID del cliente (debe existir) |
| `frecuencia_visitas` | required, integer, in:1,2,3 | Visitas mensuales (1, 2 o 3) |
| `monto` | required, numeric, min:0 | Monto mensual en ARS |
| `fecha_inicio` | required, date | Fecha de inicio (YYYY-MM-DD) |
| `fecha_vencimiento` | optional, date | Fecha de vencimiento (default: +30 días) |
| `estado` | optional, in:activo,vencido,suspendido | Estado (default: activo) |
| `observaciones` | optional, string | Notas adicionales |

### Response 201 - Creado

```json
{
  "success": true,
  "data": {
    "id": 46,
    "cliente_id": 5,
    "frecuencia_visitas": 3,
    "monto": 75000.00,
    "fecha_inicio": "2025-12-01",
    "fecha_vencimiento": "2026-11-30",
    "estado": "activo",
    "observaciones": "Abono premium con 3 visitas mensuales",
    "created_at": "2025-11-27 17:00:00"
  },
  "message": "Abono creado exitosamente"
}
```

### Response 404 - Cliente No Existe

```json
{
  "success": false,
  "message": "Cliente no encontrado"
}
```

### Response 422 - Validación Fallida

```json
{
  "success": false,
  "message": "Errores de validación",
  "errors": {
    "frecuencia_visitas": ["El campo frecuencia_visitas debe ser uno de: 1, 2, 3"],
    "monto": ["El campo monto debe ser mayor o igual a 0"]
  }
}
```

---

## PUT /abonos/:id

Actualizar abono existente.

### Request

```http
PUT /api/abonos/1
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "monto": 55000.00,
  "fecha_vencimiento": "2026-01-31",
  "observaciones": "Actualizado monto por ajuste de tarifas"
}
```

### Notas

- Solo se actualizan los campos enviados
- No es obligatorio enviar todos los campos
- Validaciones aplican solo a campos enviados

### Response 200 - Actualizado

```json
{
  "success": true,
  "data": {
    "id": 1,
    "cliente_id": 1,
    "frecuencia_visitas": 2,
    "monto": 55000.00,
    "fecha_vencimiento": "2026-01-31",
    "observaciones": "Actualizado monto por ajuste de tarifas",
    "updated_at": "2025-11-27 17:30:00"
  },
  "message": "Abono actualizado exitosamente"
}
```

### Response 404 - No Encontrado

```json
{
  "success": false,
  "message": "Abono no encontrado"
}
```

---

## DELETE /abonos/:id

Suspender abono (soft delete).

### Request

```http
DELETE /api/abonos/1
Authorization: Bearer {token}
```

### Permisos

- ⚠️ Solo usuarios con rol `admin` pueden suspender abonos

### Response 200 - Suspendido

```json
{
  "success": true,
  "message": "Abono suspendido exitosamente"
}
```

### Response 403 - Sin Permisos

```json
{
  "success": false,
  "message": "No tienes permisos para acceder a este recurso"
}
```

### Response 404 - No Encontrado

```json
{
  "success": false,
  "message": "Abono no encontrado"
}
```

### Notas

- **Soft delete**: No elimina físicamente, cambia `estado` a `suspendido`
- El cliente sigue existiendo pero sin abono activo
- Puede reactivarse creando un nuevo abono para el cliente

---

## GET /abonos/proximos-vencer

Obtener abonos próximos a vencer (para alertas).

### Request

```http
GET /api/abonos/proximos-vencer?dias=7
Authorization: Bearer {token}
```

### Query Parameters

| Parámetro | Tipo | Descripción | Default |
|-----------|------|-------------|---------|
| `dias` | integer | Días de antelación | 7 |

### Response 200 - Éxito

```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "cliente_id": 8,
      "cliente_nombre": "Fitness Center",
      "cliente_telefono": "011-5555-6666",
      "frecuencia_visitas": 2,
      "monto": 45000.00,
      "fecha_vencimiento": "2025-12-03",
      "dias_restantes": 6,
      "estado": "activo"
    },
    {
      "id": 12,
      "cliente_id": 15,
      "cliente_nombre": "Power Gym",
      "cliente_telefono": "011-7777-8888",
      "frecuencia_visitas": 1,
      "monto": 30000.00,
      "fecha_vencimiento": "2025-12-01",
      "dias_restantes": 4,
      "estado": "activo"
    }
  ]
}
```

### Notas

- Solo incluye abonos con `estado = 'activo'`
- Ordenados por fecha de vencimiento (más próximos primero)
- `dias_restantes` puede ser negativo si ya venció
- Útil para enviar recordatorios por email/WhatsApp

---

## POST /abonos/:id/renovar

Renovar abono extendiendo la fecha de vencimiento.

### Request

```http
POST /api/abonos/1/renovar
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "meses": 12
}
```

### Validaciones

| Campo | Reglas | Descripción |
|-------|--------|-------------|
| `meses` | required, integer, min:1, max:12 | Meses a extender |

### Response 200 - Renovado

```json
{
  "success": true,
  "data": {
    "id": 1,
    "cliente_id": 1,
    "fecha_vencimiento": "2026-12-31",
    "estado": "activo",
    "updated_at": "2025-11-27 18:00:00"
  },
  "message": "Abono renovado por 12 mes(es)"
}
```

### Response 404 - No Encontrado

```json
{
  "success": false,
  "message": "Abono no encontrado"
}
```

### Notas

- Extiende desde la fecha de vencimiento actual
- Si el abono estaba vencido o suspendido, lo reactiva (`estado = 'activo'`)
- Los meses se suman a la fecha actual de vencimiento
- Ejemplo: si vence el 2025-12-31 y se renueva por 6 meses → nuevo vencimiento: 2026-06-30

---

## 🎨 Sistema de Colores por Frecuencia

Los abonos tienen colores según la frecuencia de visitas:

| Frecuencia | Color | Hex | Uso |
|------------|-------|-----|-----|
| 1 visita | Verde | `#4CAF50` | Planillas de control |
| 2 visitas | Azul | `#2196F3` | Planillas de control |
| 3 visitas | Morado | `#9C27B0` | Planillas de control |

### Ejemplo de Uso en UI

```typescript
const getColorClass = (frecuencia: number) => {
  const colors = {
    1: 'bg-green-500',
    2: 'bg-blue-500',
    3: 'bg-purple-500'
  };
  return colors[frecuencia] || 'bg-gray-500';
};
```

---

## 📊 Estados de Abono

| Estado | Descripción | Color | Acciones |
|--------|-------------|-------|----------|
| `activo` | Abono vigente | Verde | Renovar, suspender |
| `vencido` | Fecha de vencimiento pasada | Rojo | Renovar, cobrar |
| `suspendido` | Suspendido manualmente | Gris | Reactivar (crear nuevo) |

---

## 🔔 Alertas y Notificaciones

### Casos de Uso

1. **Abonos próximos a vencer** (7 días antes)
   - Enviar email/WhatsApp al cliente
   - Notificar al vendedor

2. **Abonos vencidos**
   - Marcar cliente como moroso
   - Suspender acceso a servicios

3. **Renovaciones automáticas**
   - Generar factura automática
   - Extender vencimiento

### Ejemplo de Cronjob

```php
// Ejecutar diariamente
$abonosVencidos = GET /api/abonos/proximos-vencer?dias=0

foreach ($abonosVencidos as $abono) {
    // Cambiar estado a vencido
    PUT /api/abonos/{$abono['id']} {"estado": "vencido"}
    
    // Notificar cliente
    enviarEmail($abono['cliente_email'], 'Su abono ha vencido');
}
```

---

## 🚀 Ejemplos de Integración

### Crear Abono (React)

```typescript
const createAbono = async (abonoData: AbonoData) => {
  const response = await fetch('http://localhost:8000/api/abonos', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      cliente_id: abonoData.clienteId,
      frecuencia_visitas: abonoData.frecuencia,
      monto: abonoData.monto,
      fecha_inicio: new Date().toISOString().split('T')[0],
      fecha_vencimiento: addMonths(new Date(), 12).toISOString().split('T')[0],
      estado: 'activo'
    })
  });
  
  const data = await response.json();
  
  if (data.success) {
    toast.success('Abono creado exitosamente');
    return data.data;
  } else {
    toast.error(data.message);
    return null;
  }
};
```

### Verificar Abonos a Vencer

```typescript
const checkAbonosVencer = async () => {
  const response = await fetch(
    'http://localhost:8000/api/abonos/proximos-vencer?dias=15',
    {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    }
  );
  
  const data = await response.json();
  
  if (data.success && data.data.length > 0) {
    // Mostrar badge con cantidad
    setBadgeCount(data.data.length);
    
    // Mostrar notificación
    toast.info(`${data.data.length} abonos próximos a vencer`);
  }
};
```

### Renovar Abono

```typescript
const renovarAbono = async (abonoId: number, meses: number) => {
  const response = await fetch(
    `http://localhost:8000/api/abonos/${abonoId}/renovar`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({ meses })
    }
  );
  
  const data = await response.json();
  
  if (data.success) {
    toast.success(`Abono renovado por ${meses} meses`);
    refreshAbonos();
  }
};
```

---

## 💰 Modelos de Negocio

### Abono Mensual

```json
{
  "frecuencia_visitas": 2,
  "monto": 50000.00,
  "fecha_inicio": "2025-12-01",
  "fecha_vencimiento": "2026-01-01"
}
```

### Abono Anual (con descuento)

```json
{
  "frecuencia_visitas": 2,
  "monto": 540000.00,
  "fecha_inicio": "2025-12-01",
  "fecha_vencimiento": "2026-12-01",
  "observaciones": "Abono anual - 10% descuento aplicado"
}
```

### Abono Premium (3 visitas)

```json
{
  "frecuencia_visitas": 3,
  "monto": 75000.00,
  "fecha_inicio": "2025-12-01",
  "fecha_vencimiento": "2026-01-01",
  "observaciones": "Incluye atención prioritaria"
}
```

---

## 📋 Tabla de Referencia Rápida

| Endpoint | Método | Auth | Descripción |
|----------|--------|------|-------------|
| `/abonos` | GET | ✅ | Listar con filtros |
| `/abonos/:id` | GET | ✅ | Obtener uno específico |
| `/abonos` | POST | ✅ | Crear nuevo |
| `/abonos/:id` | PUT | ✅ | Actualizar |
| `/abonos/:id` | DELETE | ✅ Admin | Suspender |
| `/abonos/proximos-vencer` | GET | ✅ | Alertas de vencimiento |
| `/abonos/:id/renovar` | POST | ✅ | Extender vencimiento |

---

**Estado:** ✅ Implementado y testeado  
**Última actualización:** Noviembre 27, 2025
