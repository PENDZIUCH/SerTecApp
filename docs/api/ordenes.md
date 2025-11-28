# 📋 Órdenes de Trabajo

Endpoints para gestionar órdenes de trabajo (partes de servicio técnico).

---

## 📋 Tabla de Contenidos

- [GET /ordenes](#get-ordenes) - Listar órdenes
- [GET /ordenes/:id](#get-ordenesid) - Obtener orden
- [POST /ordenes](#post-ordenes) - Crear orden
- [PUT /ordenes/:id](#put-ordenesid) - Actualizar orden
- [DELETE /ordenes/:id](#delete-ordenesid) - Eliminar orden

---

## GET /ordenes

Listar órdenes de trabajo con filtros y paginación.

### Request

```http
GET /api/ordenes?page=1&per_page=15&estado=completado&cliente_id=5
Authorization: Bearer {token}
```

### Query Parameters

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `page` | integer | Número de página (default: 1) | `page=2` |
| `per_page` | integer | Items por página (default: 15) | `per_page=20` |
| `estado` | string | Filtrar por estado | `pendiente`, `en_progreso`, `completado` |
| `cliente_id` | integer | Filtrar por cliente | `cliente_id=5` |
| `tecnico_id` | integer | Filtrar por técnico | `tecnico_id=3` |
| `sincronizado` | boolean | Filtrar sincronizadas | `sincronizado=true` |

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "numero_parte": "OT-2025-001",
        "cliente_id": 1,
        "cliente_nombre": "Club Ateneo Gym",
        "tecnico_id": 2,
        "tecnico_nombre": "Juan Técnico",
        "fecha_trabajo": "2025-11-27",
        "hora_inicio": "09:00:00",
        "hora_fin": "11:30:00",
        "equipo_marca": "Life Fitness",
        "equipo_modelo": "IC2",
        "equipo_serie": "LF123456",
        "descripcion_trabajo": "Mantenimiento preventivo completo",
        "observaciones": "Se reemplazó banda",
        "estado": "completado",
        "total": 15000.00,
        "sincronizado": true,
        "created_at": "2025-11-27 09:00:00",
        "updated_at": "2025-11-27 11:30:00"
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 234,
    "last_page": 16
  }
}
```

### Códigos de Estado

- `200` - Lista obtenida exitosamente
- `401` - No autenticado

---

## GET /ordenes/:id

Obtener una orden específica con repuestos utilizados.

### Request

```http
GET /api/ordenes/1
Authorization: Bearer {token}
```

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "id": 1,
    "numero_parte": "OT-2025-001",
    "cliente_id": 1,
    "cliente_nombre": "Club Ateneo Gym",
    "cliente_telefono": "011-4444-5555",
    "tecnico_id": 2,
    "tecnico_nombre": "Juan Técnico",
    "fecha_trabajo": "2025-11-27",
    "hora_inicio": "09:00:00",
    "hora_fin": "11:30:00",
    "equipo_marca": "Life Fitness",
    "equipo_modelo": "IC2",
    "equipo_serie": "LF123456",
    "descripcion_trabajo": "Mantenimiento preventivo completo",
    "observaciones": "Se reemplazó banda y lubricó sistema",
    "estado": "completado",
    "firma_cliente": "data:image/png;base64,...",
    "total": 15000.00,
    "sincronizado": true,
    "repuestos": [
      {
        "id": 1,
        "repuesto_id": 10,
        "repuesto_codigo": "BANDA-IC2",
        "repuesto_descripcion": "Banda para IC2",
        "cantidad": 1,
        "precio_unitario": 5000.00,
        "subtotal": 5000.00
      }
    ],
    "created_at": "2025-11-27 09:00:00",
    "updated_at": "2025-11-27 11:30:00"
  }
}
```

### Response 404 - No Encontrado

```json
{
  "success": false,
  "message": "Orden no encontrada"
}
```

---

## POST /ordenes

Crear nueva orden de trabajo.

### Request

```http
POST /api/ordenes
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "numero_parte": "OT-2025-150",
  "cliente_id": 5,
  "tecnico_id": 2,
  "fecha_trabajo": "2025-11-28",
  "hora_inicio": "14:00:00",
  "hora_fin": "16:30:00",
  "equipo_marca": "Schwinn",
  "equipo_modelo": "AC Sport",
  "equipo_serie": "SW987654",
  "descripcion_trabajo": "Reparación de freno magnético",
  "observaciones": "Cliente reportó ruido anormal",
  "estado": "en_progreso",
  "repuestos": [
    {
      "repuesto_id": 15,
      "cantidad": 2,
      "precio_unitario": 3500.00
    }
  ],
  "sincronizado": false
}
```

### Validaciones

| Campo | Reglas | Descripción |
|-------|--------|-------------|
| `numero_parte` | required, unique | Número único de parte |
| `cliente_id` | required, exists | ID del cliente (debe existir) |
| `tecnico_id` | required, exists | ID del técnico (debe existir) |
| `fecha_trabajo` | required, date | Fecha del trabajo (YYYY-MM-DD) |
| `descripcion_trabajo` | required, min:10 | Descripción del trabajo |
| `estado` | optional, in:pendiente,en_progreso,completado | Estado (default: pendiente) |
| `repuestos` | optional, array | Array de repuestos utilizados |

### Response 201 - Creado

```json
{
  "success": true,
  "data": {
    "id": 235,
    "numero_parte": "OT-2025-150",
    "cliente_id": 5,
    "tecnico_id": 2,
    "fecha_trabajo": "2025-11-28",
    "estado": "en_progreso",
    "total": 7000.00,
    "created_at": "2025-11-27 17:00:00"
  }
}
```

### Response 422 - Validación Fallida

```json
{
  "success": false,
  "message": "Errores de validación",
  "errors": {
    "numero_parte": ["El número de parte ya existe"],
    "cliente_id": ["El cliente seleccionado no existe"],
    "descripcion_trabajo": ["La descripción debe tener al menos 10 caracteres"]
  }
}
```

### Notas

- Si se incluyen repuestos, se descuenta automáticamente del stock
- El `total` se calcula automáticamente sumando repuestos
- `sincronizado=false` para órdenes creadas offline

---

## PUT /ordenes/:id

Actualizar orden de trabajo existente.

### Request

```http
PUT /api/ordenes/1
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "estado": "completado",
  "hora_fin": "11:30:00",
  "observaciones": "Trabajo finalizado. Se reemplazó banda y lubricó sistema.",
  "firma_cliente": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUg..."
}
```

### Notas

- Solo se actualizan los campos enviados
- Si se agrega `firma_cliente`, típicamente cambia estado a `completado`
- Si se agregan/modifican repuestos, actualiza stock e inventario

### Response 200 - Actualizado

```json
{
  "success": true,
  "data": {
    "id": 1,
    "numero_parte": "OT-2025-001",
    "estado": "completado",
    "hora_fin": "11:30:00",
    "observaciones": "Trabajo finalizado. Se reemplazó banda y lubricó sistema.",
    "firma_cliente": "data:image/png;base64,...",
    "updated_at": "2025-11-27 17:30:00"
  }
}
```

### Response 404 - No Encontrado

```json
{
  "success": false,
  "message": "Orden no encontrada"
}
```

---

## DELETE /ordenes/:id

Eliminar orden de trabajo.

### Request

```http
DELETE /api/ordenes/1
Authorization: Bearer {token}
```

### Permisos

- ⚠️ Solo usuarios con rol `admin` pueden eliminar órdenes

### Response 200 - Eliminado

```json
{
  "success": true,
  "message": "Orden eliminada exitosamente"
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
  "message": "Orden no encontrada"
}
```

### Notas

- Elimina físicamente la orden de la BD
- Los repuestos utilizados **no** se devuelven al stock
- Elimina también los registros de `orden_repuestos` asociados

---

## 📊 Estados de Orden

| Estado | Descripción | Color | Acciones |
|--------|-------------|-------|----------|
| `pendiente` | Orden creada, sin iniciar | Gris | Asignar técnico, iniciar |
| `en_progreso` | Técnico trabajando | Amarillo | Agregar repuestos, completar |
| `completado` | Trabajo finalizado | Verde | Ver, exportar, facturar |

---

## 🛠️ Gestión de Repuestos

### Agregar Repuestos a Orden

Al crear o actualizar una orden con repuestos:

```json
{
  "repuestos": [
    {
      "repuesto_id": 10,
      "cantidad": 2,
      "precio_unitario": 5000.00
    }
  ]
}
```

El backend automáticamente:
1. Verifica stock disponible
2. Descuenta del inventario
3. Registra movimiento en `movimientos_repuestos`
4. Calcula subtotal y total de la orden

### Validaciones de Stock

Si no hay stock suficiente:

```json
{
  "success": false,
  "message": "Stock insuficiente para repuesto BANDA-IC2. Disponible: 0, Requerido: 2"
}
```

---

## ✍️ Firma Digital

### Captura de Firma

La firma del cliente se envía como **Data URL** (base64):

```
data:image/png;base64,iVBORw0KGgoAAAANSUhEUg...
```

### Frontend (Canvas)

```typescript
const captureSignature = () => {
  const canvas = signatureCanvasRef.current;
  const dataUrl = canvas.toDataURL('image/png');
  
  updateOrden(ordenId, {
    firma_cliente: dataUrl,
    estado: 'completado'
  });
};
```

### Notas

- Firma se guarda como TEXT en BD
- Tamaño recomendado: canvas 400x200px
- Compresión: PNG con calidad media

---

## 🔄 Sincronización Offline

### Campo `sincronizado`

- `false` - Orden creada offline, pendiente sincronización
- `true` - Orden sincronizada con servidor

### Flujo Offline

```
1. Técnico sin conexión → Crear orden (sincronizado=false)
2. Guardar en IndexedDB local
3. Conexión restaurada → POST /ordenes (sincronizado=false)
4. Backend guarda → Retorna ID real
5. Frontend actualiza → PUT /ordenes/:id (sincronizado=true)
```

### Filtrar No Sincronizadas

```http
GET /api/ordenes?sincronizado=false
```

---

## 🚀 Ejemplos de Integración

### Crear Orden Completa (React)

```typescript
const createOrden = async (ordenData: OrdenData) => {
  const response = await fetch('http://localhost:8000/api/ordenes', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      numero_parte: `OT-${Date.now()}`,
      cliente_id: ordenData.clienteId,
      tecnico_id: currentUser.id,
      fecha_trabajo: new Date().toISOString().split('T')[0],
      hora_inicio: new Date().toTimeString().split(' ')[0],
      descripcion_trabajo: ordenData.descripcion,
      estado: 'en_progreso',
      repuestos: ordenData.repuestos,
      sincronizado: navigator.onLine
    })
  });
  
  const data = await response.json();
  
  if (data.success) {
    toast.success('Orden creada exitosamente');
    return data.data;
  } else {
    toast.error(data.message);
    return null;
  }
};
```

### Completar Orden con Firma

```typescript
const completeOrden = async (ordenId: number, signature: string) => {
  const response = await fetch(`http://localhost:8000/api/ordenes/${ordenId}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      estado: 'completado',
      hora_fin: new Date().toTimeString().split(' ')[0],
      firma_cliente: signature,
      sincronizado: true
    })
  });
  
  const data = await response.json();
  return data.success;
};
```

### Listar Órdenes del Día

```typescript
const fetchOrdenesHoy = async () => {
  const today = new Date().toISOString().split('T')[0];
  
  const response = await fetch(
    `http://localhost:8000/api/ordenes?fecha_trabajo=${today}`,
    {
      headers: {
        'Authorization': `Bearer ${token}`
      }
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
| `/ordenes` | GET | ✅ | Listar con filtros |
| `/ordenes/:id` | GET | ✅ | Obtener una específica |
| `/ordenes` | POST | ✅ | Crear nueva |
| `/ordenes/:id` | PUT | ✅ | Actualizar |
| `/ordenes/:id` | DELETE | ✅ Admin | Eliminar |

---

**Estado:** ✅ Implementado y testeado  
**Última actualización:** Noviembre 27, 2025
