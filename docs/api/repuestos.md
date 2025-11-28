# 🔧 Repuestos - Gestión de Inventario

Endpoints para administrar repuestos, stock y movimientos de inventario.

---

## 📋 Tabla de Contenidos

- [GET /repuestos](#get-repuestos) - Listar repuestos
- [GET /repuestos/:id](#get-repuestosid) - Obtener repuesto con historial
- [POST /repuestos](#post-repuestos) - Crear repuesto
- [PUT /repuestos/:id](#put-repuestosid) - Actualizar repuesto
- [DELETE /repuestos/:id](#delete-repuestosid) - Eliminar repuesto
- [POST /repuestos/:id/entrada](#post-repuestosidentrada) - Registrar entrada de stock
- [POST /repuestos/:id/salida](#post-repuestosidsalida) - Registrar salida de stock
- [GET /repuestos/alertas/stock-bajo](#get-repuestosalertasstock-bajo) - Stock bajo

---

## GET /repuestos

Listar repuestos con filtros y paginación.

### Request

```http
GET /api/repuestos?page=1&per_page=15&search=banda&stock_bajo=true
Authorization: Bearer {token}
```

### Query Parameters

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `page` | integer | Número de página (default: 1) | `page=2` |
| `per_page` | integer | Items por página (default: 15) | `per_page=20` |
| `search` | string | Buscar en código, descripción o marca | `search=banda` |
| `stock_bajo` | boolean | Solo repuestos con stock bajo | `stock_bajo=true` |

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "codigo": "BANDA-IC2",
        "descripcion": "Banda de transmisión para IC2",
        "marca": "Schwinn",
        "modelo": "IC2",
        "precio_costo": 3500.00,
        "precio_venta": 5000.00,
        "stock_actual": 8,
        "stock_minimo": 5,
        "stock_maximo": 20,
        "ubicacion": "Estante A3",
        "observaciones": "Revisar stock mensualmente",
        "estado_stock": "stock_normal",
        "created_at": "2025-01-10 09:00:00",
        "updated_at": "2025-11-25 14:30:00"
      },
      {
        "id": 2,
        "codigo": "RULEMAN-LF",
        "descripcion": "Ruleman antiretroceso Life Fitness",
        "marca": "Life Fitness",
        "modelo": "GX",
        "precio_costo": 8500.00,
        "precio_venta": 12000.00,
        "stock_actual": 2,
        "stock_minimo": 5,
        "stock_maximo": 15,
        "ubicacion": "Estante B1",
        "observaciones": null,
        "estado_stock": "stock_bajo",
        "created_at": "2025-02-15 10:30:00",
        "updated_at": "2025-11-20 16:45:00"
      }
    ],
    "pagination": {
      "total": 156,
      "per_page": 15,
      "current_page": 1,
      "last_page": 11
    }
  }
}
```

### Estados de Stock

| Estado | Condición | Color |
|--------|-----------|-------|
| `sin_stock` | `stock_actual <= 0` | Rojo |
| `stock_bajo` | `stock_actual <= stock_minimo` | Amarillo |
| `stock_normal` | `stock_minimo < stock_actual < stock_maximo` | Verde |
| `stock_alto` | `stock_actual >= stock_maximo` | Azul |

---

## GET /repuestos/:id

Obtener repuesto específico con historial de movimientos.

### Request

```http
GET /api/repuestos/1
Authorization: Bearer {token}
```

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "id": 1,
    "codigo": "BANDA-IC2",
    "descripcion": "Banda de transmisión para IC2",
    "marca": "Schwinn",
    "modelo": "IC2",
    "precio_costo": 3500.00,
    "precio_venta": 5000.00,
    "stock_actual": 8,
    "stock_minimo": 5,
    "stock_maximo": 20,
    "ubicacion": "Estante A3",
    "observaciones": "Revisar stock mensualmente",
    "estado_stock": "stock_normal",
    "movimientos": [
      {
        "id": 45,
        "repuesto_id": 1,
        "tipo": "salida",
        "cantidad": 2,
        "motivo": "Usado en orden de trabajo OT-2025-150",
        "referencia_id": 150,
        "usuario_id": 2,
        "usuario_nombre": "Juan Técnico",
        "created_at": "2025-11-27 10:30:00"
      },
      {
        "id": 38,
        "repuesto_id": 1,
        "tipo": "entrada",
        "cantidad": 10,
        "motivo": "Compra a proveedor ABC",
        "referencia_id": null,
        "usuario_id": 1,
        "usuario_nombre": "Admin",
        "created_at": "2025-11-20 14:00:00"
      }
    ],
    "created_at": "2025-01-10 09:00:00",
    "updated_at": "2025-11-27 10:30:00"
  }
}
```

### Response 404 - No Encontrado

```json
{
  "success": false,
  "message": "Repuesto no encontrado"
}
```

### Notas

- Incluye últimos 20 movimientos ordenados por fecha (más recientes primero)
- `tipo` puede ser `entrada` o `salida`
- `referencia_id` vincula con órdenes de trabajo o compras

---

## POST /repuestos

Crear nuevo repuesto en el inventario.

### Request

```http
POST /api/repuestos
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "codigo": "CABLE-TR01",
  "descripcion": "Cable de acero para treadmill",
  "marca": "Generic",
  "modelo": "TR01",
  "precio_costo": 1500.00,
  "precio_venta": 2500.00,
  "stock_actual": 15,
  "stock_minimo": 5,
  "stock_maximo": 30,
  "ubicacion": "Estante C2",
  "observaciones": "Compatible con múltiples modelos"
}
```

### Validaciones

| Campo | Reglas | Descripción |
|-------|--------|-------------|
| `codigo` | required, alphanumeric, max:50, unique | Código único del repuesto |
| `descripcion` | required, max:255 | Descripción del repuesto |
| `precio_costo` | optional, numeric, min:0 | Precio de costo en ARS |
| `precio_venta` | required, numeric, min:0 | Precio de venta en ARS |
| `stock_actual` | optional, integer, min:0 | Stock inicial (default: 0) |
| `stock_minimo` | optional, integer, min:0 | Stock mínimo (default: 5) |
| `stock_maximo` | optional, integer, min:0 | Stock máximo (default: 100) |

### Response 201 - Creado

```json
{
  "success": true,
  "data": {
    "id": 157,
    "codigo": "CABLE-TR01",
    "descripcion": "Cable de acero para treadmill",
    "marca": "Generic",
    "modelo": "TR01",
    "precio_costo": 1500.00,
    "precio_venta": 2500.00,
    "stock_actual": 15,
    "stock_minimo": 5,
    "stock_maximo": 30,
    "ubicacion": "Estante C2",
    "estado_stock": "stock_normal",
    "created_at": "2025-11-27 18:00:00"
  },
  "message": "Repuesto creado exitosamente"
}
```

### Response 400 - Código Duplicado

```json
{
  "success": false,
  "message": "El código de repuesto ya existe"
}
```

### Response 422 - Validación Fallida

```json
{
  "success": false,
  "message": "Errores de validación",
  "errors": {
    "codigo": ["El campo codigo es requerido"],
    "precio_venta": ["El campo precio_venta debe ser mayor o igual a 0"]
  }
}
```

### Notas

- Si `stock_actual > 0`, se crea automáticamente un movimiento de entrada con motivo "Stock inicial"
- El código debe ser único en toda la BD

---

## PUT /repuestos/:id

Actualizar información del repuesto.

### Request

```http
PUT /api/repuestos/1
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "precio_venta": 5500.00,
  "stock_minimo": 8,
  "ubicacion": "Estante A5",
  "observaciones": "Actualizado por cambio de proveedor"
}
```

### Notas

- ⚠️ **No actualiza** `stock_actual` directamente
- Para modificar stock usar: `/repuestos/:id/entrada` o `/repuestos/:id/salida`
- Solo se actualizan los campos enviados

### Response 200 - Actualizado

```json
{
  "success": true,
  "data": {
    "id": 1,
    "codigo": "BANDA-IC2",
    "precio_venta": 5500.00,
    "stock_minimo": 8,
    "ubicacion": "Estante A5",
    "observaciones": "Actualizado por cambio de proveedor",
    "updated_at": "2025-11-27 18:15:00"
  },
  "message": "Repuesto actualizado exitosamente"
}
```

### Response 404 - No Encontrado

```json
{
  "success": false,
  "message": "Repuesto no encontrado"
}
```

---

## DELETE /repuestos/:id

Eliminar repuesto del inventario.

### Request

```http
DELETE /api/repuestos/1
Authorization: Bearer {token}
```

### Permisos

- ⚠️ Solo usuarios con rol `admin` pueden eliminar repuestos

### Response 200 - Eliminado

```json
{
  "success": true,
  "message": "Repuesto eliminado exitosamente"
}
```

### Response 400 - Usado en Órdenes

```json
{
  "success": false,
  "message": "No se puede eliminar: repuesto usado en órdenes de trabajo"
}
```

### Response 403 - Sin Permisos

```json
{
  "success": false,
  "message": "No tienes permisos para acceder a este recurso"
}
```

### Notas

- **Hard delete**: Elimina físicamente de la BD
- No se puede eliminar si fue usado en órdenes de trabajo
- Elimina también todos los movimientos asociados

---

## POST /repuestos/:id/entrada

Registrar entrada de stock (compra, devolución, ajuste).

### Request

```http
POST /api/repuestos/1/entrada
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "cantidad": 10,
  "motivo": "Compra a proveedor XYZ - Orden #12345",
  "orden_compra": "OC-2025-089"
}
```

### Validaciones

| Campo | Reglas | Descripción |
|-------|--------|-------------|
| `cantidad` | required, integer, min:1 | Cantidad a ingresar |
| `motivo` | required, max:255 | Razón de la entrada |
| `orden_compra` | optional, string | Referencia a orden de compra |

### Response 200 - Registrado

```json
{
  "success": true,
  "data": {
    "stock_anterior": 8,
    "cantidad_ingresada": 10,
    "stock_actual": 18
  },
  "message": "Entrada registrada exitosamente"
}
```

### Response 404 - No Encontrado

```json
{
  "success": false,
  "message": "Repuesto no encontrado"
}
```

### Notas

- Incrementa automáticamente `stock_actual`
- Crea registro en `movimientos_repuestos`
- Incluye `usuario_id` del usuario autenticado
- Útil para: compras, devoluciones, ajustes de inventario

---

## POST /repuestos/:id/salida

Registrar salida de stock (uso en trabajo, venta, pérdida).

### Request

```http
POST /api/repuestos/1/salida
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "cantidad": 2,
  "motivo": "Usado en orden de trabajo OT-2025-150",
  "orden_trabajo_id": 150
}
```

### Validaciones

| Campo | Reglas | Descripción |
|-------|--------|-------------|
| `cantidad` | required, integer, min:1 | Cantidad a retirar |
| `motivo` | required, max:255 | Razón de la salida |
| `orden_trabajo_id` | optional, integer | Referencia a orden de trabajo |

### Response 200 - Registrado

```json
{
  "success": true,
  "data": {
    "stock_anterior": 18,
    "cantidad_retirada": 2,
    "stock_actual": 16,
    "alerta_stock_bajo": false
  },
  "message": "Salida registrada exitosamente"
}
```

### Response 400 - Stock Insuficiente

```json
{
  "success": false,
  "message": "Stock insuficiente"
}
```

### Response 404 - No Encontrado

```json
{
  "success": false,
  "message": "Repuesto no encontrado"
}
```

### Notas

- Decrementa automáticamente `stock_actual`
- Verifica que haya stock suficiente
- `alerta_stock_bajo: true` si el stock resultante <= `stock_minimo`
- Crea registro en `movimientos_repuestos`

---

## GET /repuestos/alertas/stock-bajo

Obtener repuestos con stock bajo o sin stock.

### Request

```http
GET /api/repuestos/alertas/stock-bajo
Authorization: Bearer {token}
```

### Response 200 - Éxito

```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "codigo": "RULEMAN-LF",
      "descripcion": "Ruleman antiretroceso Life Fitness",
      "marca": "Life Fitness",
      "stock_actual": 2,
      "stock_minimo": 5,
      "cantidad_faltante": 3
    },
    {
      "id": 15,
      "codigo": "CABLE-SW",
      "descripcion": "Cable de freno Schwinn",
      "marca": "Schwinn",
      "stock_actual": 0,
      "stock_minimo": 3,
      "cantidad_faltante": 3
    }
  ]
}
```

### Notas

- Solo incluye repuestos donde `stock_actual <= stock_minimo`
- Ordenados por stock actual (menor primero)
- `cantidad_faltante = stock_minimo - stock_actual`
- Útil para generar órdenes de compra automáticas

---

## 📊 Gestión de Movimientos

### Tipos de Movimiento

| Tipo | Uso | Afecta Stock |
|------|-----|--------------|
| `entrada` | Compras, devoluciones, ajustes positivos | +stock |
| `salida` | Usado en trabajos, ventas, pérdidas | -stock |

### Tracking Completo

Cada movimiento registra:
- ✅ Cantidad
- ✅ Motivo (descripción)
- ✅ Usuario que lo realizó
- ✅ Fecha y hora
- ✅ Referencia (orden de trabajo, orden de compra)

---

## 🔔 Alertas de Stock

### Casos de Alerta

1. **Stock Bajo** (`stock_actual <= stock_minimo`)
   - Mostrar badge en UI
   - Email al encargado de compras
   - Generar sugerencia de orden de compra

2. **Sin Stock** (`stock_actual = 0`)
   - Alerta crítica
   - Bloquear uso en órdenes nuevas
   - Priorizar compra

3. **Stock Alto** (`stock_actual >= stock_maximo`)
   - Revisar si hay sobre-stock
   - Considerar promociones

### Ejemplo de Notificación

```typescript
// Check diario de stock bajo
const checkStockBajo = async () => {
  const response = await fetch(
    'http://localhost:8000/api/repuestos/alertas/stock-bajo',
    { headers: { Authorization: `Bearer ${token}` } }
  );
  
  const data = await response.json();
  
  if (data.success && data.data.length > 0) {
    toast.warning(`${data.data.length} repuestos con stock bajo`);
    sendEmailToCompras(data.data);
  }
};
```

---

## 🚀 Ejemplos de Integración

### Registrar Entrada de Stock

```typescript
const registrarEntrada = async (repuestoId: number, cantidad: number, motivo: string) => {
  const response = await fetch(
    `http://localhost:8000/api/repuestos/${repuestoId}/entrada`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({ cantidad, motivo })
    }
  );
  
  const data = await response.json();
  
  if (data.success) {
    toast.success(`Entrada registrada. Stock actual: ${data.data.stock_actual}`);
    refreshInventario();
  }
};
```

### Usar Repuesto en Orden

```typescript
const usarRepuestoEnOrden = async (
  repuestoId: number,
  cantidad: number,
  ordenId: number
) => {
  const response = await fetch(
    `http://localhost:8000/api/repuestos/${repuestoId}/salida`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        cantidad,
        motivo: `Usado en orden ${ordenId}`,
        orden_trabajo_id: ordenId
      })
    }
  );
  
  const data = await response.json();
  
  if (data.success) {
    if (data.data.alerta_stock_bajo) {
      toast.warning('¡Atención! Stock bajo después de este uso');
    }
    return true;
  } else {
    toast.error(data.message);
    return false;
  }
};
```

### Buscar Repuesto

```typescript
const searchRepuestos = async (query: string) => {
  const response = await fetch(
    `http://localhost:8000/api/repuestos?search=${encodeURIComponent(query)}`,
    {
      headers: { Authorization: `Bearer ${token}` }
    }
  );
  
  const data = await response.json();
  return data.success ? data.data.data : [];
};
```

---

## 📋 Tabla de Referencia Rápida

| Endpoint | Método | Auth | Descripción |
|----------|--------|------|-------------|
| `/repuestos` | GET | ✅ | Listar con filtros |
| `/repuestos/:id` | GET | ✅ | Obtener con historial |
| `/repuestos` | POST | ✅ | Crear nuevo |
| `/repuestos/:id` | PUT | ✅ | Actualizar info |
| `/repuestos/:id` | DELETE | ✅ Admin | Eliminar |
| `/repuestos/:id/entrada` | POST | ✅ | Ingresar stock |
| `/repuestos/:id/salida` | POST | ✅ | Retirar stock |
| `/repuestos/alertas/stock-bajo` | GET | ✅ | Stock bajo/sin stock |

---

**Estado:** ✅ Implementado y testeado  
**Última actualización:** Noviembre 27, 2025
