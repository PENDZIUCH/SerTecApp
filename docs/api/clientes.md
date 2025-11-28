# 👥 Clientes - Gestión de Clientes

Endpoints para administrar clientes (abonados y esporádicos).

---

## 📋 Tabla de Contenidos

- [GET /clientes](#get-clientes) - Listar clientes
- [GET /clientes/:id](#get-clientesid) - Obtener cliente
- [POST /clientes](#post-clientes) - Crear cliente
- [PUT /clientes/:id](#put-clientesid) - Actualizar cliente
- [DELETE /clientes/:id](#delete-clientesid) - Eliminar cliente

---

## GET /clientes

Listar todos los clientes con paginación y filtros.

### Request

```http
GET /api/clientes?page=1&per_page=15&tipo=abonado&estado=activo&search=gym
Authorization: Bearer {token}
```

### Query Parameters

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `page` | integer | Número de página (default: 1) | `page=2` |
| `per_page` | integer | Items por página (default: 15) | `per_page=20` |
| `tipo` | string | Filtrar por tipo | `abonado` o `esporadico` |
| `estado` | string | Filtrar por estado | `activo`, `inactivo`, `moroso` |
| `search` | string | Buscar en nombre, razón social o CUIT | `search=gym` |

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "nombre": "Club Ateneo Gym",
        "razon_social": "Ateneo Gym S.A.",
        "cuit": "30-12345678-9",
        "tipo": "abonado",
        "frecuencia_visitas": 2,
        "direccion": "Av. Principal 123",
        "localidad": "Don Torcuato",
        "provincia": "Buenos Aires",
        "codigo_postal": "1611",
        "telefono": "011-4444-5555",
        "email": "info@ateneogym.com",
        "contacto_nombre": "Juan Pérez",
        "contacto_telefono": "011-4444-5556",
        "estado": "activo",
        "notas": "Cliente premium",
        "created_at": "2025-01-15 10:30:00",
        "updated_at": "2025-11-20 15:45:00"
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 45,
    "last_page": 3
  }
}
```

### Códigos de Estado

- `200` - Lista de clientes obtenida exitosamente
- `401` - No autenticado

---

## GET /clientes/:id

Obtener un cliente específico con información de abono activo.

### Request

```http
GET /api/clientes/1
Authorization: Bearer {token}
```

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Club Ateneo Gym",
    "razon_social": "Ateneo Gym S.A.",
    "cuit": "30-12345678-9",
    "tipo": "abonado",
    "frecuencia_visitas": 2,
    "direccion": "Av. Principal 123",
    "localidad": "Don Torcuato",
    "provincia": "Buenos Aires",
    "codigo_postal": "1611",
    "telefono": "011-4444-5555",
    "email": "info@ateneogym.com",
    "contacto_nombre": "Juan Pérez",
    "contacto_telefono": "011-4444-5556",
    "estado": "activo",
    "notas": "Cliente premium",
    "abono_id": 5,
    "monto_mensual": 50000.00,
    "fecha_inicio": "2025-01-01",
    "abono_estado": "activo",
    "color_hex": "#4CAF50",
    "color_nombre": "Verde",
    "created_at": "2025-01-15 10:30:00",
    "updated_at": "2025-11-20 15:45:00"
  }
}
```

### Response 404 - No Encontrado

```json
{
  "success": false,
  "message": "Cliente no encontrado"
}
```

### Notas

- Si el cliente tiene abono activo, incluye información del abono
- El color corresponde a la frecuencia de visitas configurada

---

## POST /clientes

Crear nuevo cliente.

### Request

```http
POST /api/clientes
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "nombre": "Fitness Center",
  "razon_social": "Fitness Center SRL",
  "cuit": "30-98765432-1",
  "tipo": "abonado",
  "frecuencia_visitas": 3,
  "direccion": "Calle Falsa 123",
  "localidad": "San Isidro",
  "provincia": "Buenos Aires",
  "codigo_postal": "1642",
  "telefono": "011-5555-6666",
  "email": "contacto@fitnesscenter.com",
  "contacto_nombre": "María González",
  "contacto_telefono": "011-5555-6667",
  "estado": "activo",
  "notas": "Interesado en abono premium"
}
```

### Validaciones

| Campo | Reglas | Descripción |
|-------|--------|-------------|
| `nombre` | required, min:3, max:200 | Nombre del cliente |
| `tipo` | optional, in:abonado,esporadico | Tipo de cliente (default: esporadico) |
| `frecuencia_visitas` | optional, integer, min:0, max:10 | Visitas mensuales (default: 0) |
| `email` | optional, email | Email válido |
| `cuit` | optional, cuit | CUIT argentino válido (XX-XXXXXXXX-X) |
| `estado` | optional, in:activo,inactivo,moroso | Estado (default: activo) |

### Response 201 - Creado

```json
{
  "success": true,
  "data": {
    "id": 46,
    "nombre": "Fitness Center",
    "razon_social": "Fitness Center SRL",
    "tipo": "abonado",
    "frecuencia_visitas": 3,
    "estado": "activo",
    "created_at": "2025-11-27 16:30:00"
  }
}
```

### Response 422 - Validación Fallida

```json
{
  "success": false,
  "message": "Errores de validación",
  "errors": {
    "nombre": ["El campo nombre es requerido"],
    "email": ["El campo email debe ser un email válido"],
    "tipo": ["El campo tipo debe ser uno de: abonado, esporadico"]
  }
}
```

---

## PUT /clientes/:id

Actualizar cliente existente.

### Request

```http
PUT /api/clientes/1
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "nombre": "Club Ateneo Gym Premium",
  "telefono": "011-4444-9999",
  "email": "nuevo@ateneogym.com",
  "estado": "activo",
  "notas": "Actualizado a plan premium"
}
```

### Notas

- Solo se actualizan los campos enviados
- No es necesario enviar todos los campos
- Validaciones aplican solo a campos enviados

### Response 200 - Actualizado

```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Club Ateneo Gym Premium",
    "telefono": "011-4444-9999",
    "email": "nuevo@ateneogym.com",
    "estado": "activo",
    "notas": "Actualizado a plan premium",
    "updated_at": "2025-11-27 16:45:00"
  }
}
```

### Response 404 - No Encontrado

```json
{
  "success": false,
  "message": "Cliente no encontrado"
}
```

---

## DELETE /clientes/:id

Eliminar cliente (soft delete - cambiar estado a inactivo).

### Request

```http
DELETE /api/clientes/1
Authorization: Bearer {token}
```

### Permisos

- ⚠️ Solo usuarios con rol `admin` pueden eliminar clientes

### Response 200 - Eliminado

```json
{
  "success": true,
  "message": "Cliente eliminado exitosamente"
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
  "message": "Cliente no encontrado"
}
```

### Notas

- **Soft delete**: No elimina físicamente, cambia `estado` a `inactivo`
- Los clientes inactivos siguen en la BD pero no aparecen en listados por defecto
- Órdenes de trabajo y abonos asociados se mantienen

---

## 📊 Tipos de Cliente

### Abonado

- Cliente con contrato mensual de mantenimiento
- `tipo = "abonado"`
- Tiene `frecuencia_visitas` (1, 2 o 3 visitas mensuales)
- Asociado a un abono activo
- Colores asignados según frecuencia

### Esporádico

- Cliente sin abono mensual
- `tipo = "esporadico"`
- `frecuencia_visitas = 0`
- Se le factura por cada trabajo realizado

---

## 🎨 Sistema de Colores

Los clientes abonados tienen colores según frecuencia:

| Frecuencia | Color | Hex | Uso |
|------------|-------|-----|-----|
| 1 visita | Verde | `#4CAF50` | Planillas de control |
| 2 visitas | Azul | `#2196F3` | Planillas de control |
| 3 visitas | Morado | `#9C27B0` | Planillas de control |

---

## 🚀 Ejemplos de Integración

### Listar Clientes Activos (React)

```typescript
const fetchClientes = async (page = 1) => {
  const response = await fetch(
    `http://localhost:8000/api/clientes?page=${page}&estado=activo`,
    {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    }
  );
  
  const data = await response.json();
  
  if (data.success) {
    setClientes(data.data.data);
    setPagination({
      currentPage: data.data.current_page,
      lastPage: data.data.last_page,
      total: data.data.total
    });
  }
};
```

### Buscar Cliente

```typescript
const searchClientes = async (query: string) => {
  const response = await fetch(
    `http://localhost:8000/api/clientes?search=${encodeURIComponent(query)}`,
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

### Crear Cliente

```typescript
const createCliente = async (clienteData: ClienteData) => {
  const response = await fetch('http://localhost:8000/api/clientes', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(clienteData)
  });
  
  const data = await response.json();
  
  if (data.success) {
    toast.success('Cliente creado exitosamente');
    return data.data;
  } else {
    toast.error(data.message);
    return null;
  }
};
```

---

## 📋 Tabla de Referencia Rápida

| Endpoint | Método | Auth | Descripción |
|----------|--------|------|-------------|
| `/clientes` | GET | ✅ | Listar con filtros y paginación |
| `/clientes/:id` | GET | ✅ | Obtener uno específico |
| `/clientes` | POST | ✅ | Crear nuevo |
| `/clientes/:id` | PUT | ✅ | Actualizar |
| `/clientes/:id` | DELETE | ✅ Admin | Eliminar (soft) |

---

**Estado:** ✅ Implementado y testeado  
**Última actualización:** Noviembre 27, 2025
