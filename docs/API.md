# 📡 SerTecApp - API Documentation

## Base URL
```
Development: http://localhost:8000/api
Production: https://api.sertecapp.pendziuch.com/api
```

## Authentication
Todas las peticiones (excepto login) requieren token JWT en header:
```
Authorization: Bearer {token}
```

---

## 🔐 Authentication Endpoints

### POST /auth/login
Login de usuario

**Request:**
```json
{
  "email": "admin@sertecapp.com",
  "password": "admin123"
}
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
      "id": 1,
      "nombre": "Admin",
      "email": "admin@sertecapp.com",
      "rol": "admin"
    }
  }
}
```

### POST /auth/logout
Cierre de sesión

**Response 200:**
```json
{
  "success": true,
  "message": "Sesión cerrada correctamente"
}
```

### GET /auth/me
Obtener usuario actual

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Admin",
    "email": "admin@sertecapp.com",
    "rol": "admin",
    "activo": true
  }
}
```

---

## 👥 Clientes Endpoints

### GET /clientes
Listar clientes (paginado)

**Query Params:**
- `page` (default: 1)
- `per_page` (default: 15)
- `tipo` (abonado | esporadico)
- `estado` (activo | inactivo | moroso)
- `search` (busca en nombre, razon_social, cuit)

**Response 200:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "nombre": "Club Ateneo Gym",
        "tipo": "abonado",
        "frecuencia_visitas": 2,
        "estado": "activo",
        "telefono": "011-4444-5555",
        "email": "contacto@ateneoclub.com",
        ...
      }
    ],
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72
  }
}
```

### GET /clientes/:id
Obtener cliente por ID

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Club Ateneo Gym",
    "razon_social": "Club Deportivo Ateneo S.A.",
    "cuit": "30-12345678-9",
    "tipo": "abonado",
    "frecuencia_visitas": 2,
    "direccion": "Av. Principal 1234",
    "localidad": "Don Torcuato",
    "provincia": "Buenos Aires",
    "telefono": "011-4444-5555",
    "email": "contacto@ateneoclub.com",
    "estado": "activo",
    "abono_activo": {
      "id": 5,
      "monto_mensual": 85000,
      "fecha_inicio": "2025-01-01"
    },
    ...
  }
}
```

### POST /clientes
Crear nuevo cliente

**Request:**
```json
{
  "nombre": "Nuevo Gym",
  "razon_social": "Gimnasio Nuevo S.R.L.",
  "cuit": "30-98765432-1",
  "tipo": "abonado",
  "frecuencia_visitas": 1,
  "direccion": "Calle Falsa 123",
  "localidad": "San Miguel",
  "provincia": "Buenos Aires",
  "telefono": "011-1234-5678",
  "email": "info@nuevogym.com"
}
```

**Response 201:**
```json
{
  "success": true,
  "data": {
    "id": 15,
    "nombre": "Nuevo Gym",
    ...
  },
  "message": "Cliente creado exitosamente"
}
```

### PUT /clientes/:id
Actualizar cliente

**Request:** (mismos campos que POST, todos opcionales)

**Response 200:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Cliente actualizado exitosamente"
}
```

### DELETE /clientes/:id
Eliminar cliente

**Response 200:**
```json
{
  "success": true,
  "message": "Cliente eliminado exitosamente"
}
```

---

## 📋 Órdenes de Trabajo Endpoints

### GET /ordenes
Listar órdenes (paginado)

**Query Params:**
- `page`, `per_page`
- `estado` (pendiente | en_progreso | completado | cancelado)
- `cliente_id`
- `tecnico_id`
- `fecha_desde`, `fecha_hasta`
- `sincronizado` (true | false)

**Response 200:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "numero_parte": "OT-2025-001",
        "fecha_trabajo": "2025-11-13",
        "cliente": {
          "id": 1,
          "nombre": "Club Ateneo Gym"
        },
        "tecnico": {
          "id": 2,
          "nombre": "Juan Pérez"
        },
        "descripcion_trabajo": "Cambio de banda",
        "estado": "completado",
        "total": 66500,
        "sincronizado": true
      }
    ],
    ...
  }
}
```

### POST /ordenes
Crear orden de trabajo

**Request:**
```json
{
  "numero_parte": "OT-2025-002",
  "cliente_id": 1,
  "tecnico_id": 2,
  "fecha_trabajo": "2025-11-14",
  "hora_inicio": "10:00",
  "equipo_marca": "Body Fitness",
  "equipo_modelo": "PT300",
  "descripcion_trabajo": "Cambio de banda en cinta",
  "repuestos": [
    {
      "repuesto_id": 5,
      "cantidad": 1,
      "precio_unitario": 66500
    }
  ]
}
```

**Response 201:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Orden creada exitosamente"
}
```

---

## 💰 Facturación Endpoints

### POST /facturas
Crear factura

**Request:**
```json
{
  "cliente_id": 1,
  "tipo": "B",
  "items": [
    {
      "descripcion": "Mantenimiento preventivo",
      "cantidad": 1,
      "precio_unitario": 50000
    }
  ],
  "orden_trabajo_id": 1
}
```

**Response 201:**
```json
{
  "success": true,
  "data": {
    "id": 10,
    "numero_factura": "B-0001-00000123",
    "total": 50000,
    "estado": "pendiente"
  }
}
```

### POST /facturas/:id/enviar-tango
Enviar factura a Tango

**Response 200:**
```json
{
  "success": true,
  "data": {
    "factura_id": 10,
    "tango_status": "aprobada",
    "tango_cae": "70123456789012",
    "tango_vencimiento": "2025-11-23"
  },
  "message": "Factura enviada a Tango exitosamente"
}
```

---

## 📦 Repuestos Endpoints

### GET /repuestos
Listar repuestos

**Query Params:**
- `search` (busca en código, descripción)
- `stock_bajo` (true | false) - filtrar solo con stock bajo

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "codigo": "BANDA-BF-PT300",
      "descripcion": "Banda para cinta Body Fitness PT300",
      "stock_actual": 3,
      "stock_minimo": 2,
      "precio_unitario": 66500,
      "activo": true
    }
  ]
}
```

---

## 🔄 Sincronización Offline

### POST /sync/batch
Sincronizar múltiples cambios offline

**Request:**
```json
{
  "items": [
    {
      "tabla": "ordenes",
      "registro_id": 0,
      "accion": "create",
      "datos": { ... }
    },
    {
      "tabla": "ordenes",
      "registro_id": 15,
      "accion": "update",
      "datos": { ... }
    }
  ]
}
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "sincronizados": 2,
    "errores": 0,
    "detalles": [...]
  }
}
```

---

## ⚙️ Configuración

### GET /config/frecuencias
Obtener configuración de frecuencias

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "frecuencia_visitas": 1,
      "nombre": "1 Visita Mensual",
      "color_hex": "#22C55E",
      "color_nombre": "Verde",
      "activo": true
    }
  ]
}
```

### PUT /config/frecuencias/:id
Actualizar configuración de frecuencia

---

## 📊 Reportes

### GET /reportes/dashboard
Dashboard principal con KPIs

**Response 200:**
```json
{
  "success": true,
  "data": {
    "ordenes_mes": 45,
    "facturacion_mes": 2400000,
    "clientes_activos": 72,
    "stock_bajo": 5,
    "ordenes_pendientes": 12
  }
}
```

---

## ❌ Error Responses

**400 Bad Request:**
```json
{
  "success": false,
  "message": "Datos inválidos",
  "errors": {
    "email": ["El email es requerido"],
    "password": ["La contraseña debe tener al menos 6 caracteres"]
  }
}
```

**401 Unauthorized:**
```json
{
  "success": false,
  "message": "No autenticado"
}
```

**403 Forbidden:**
```json
{
  "success": false,
  "message": "No tienes permisos para esta acción"
}
```

**404 Not Found:**
```json
{
  "success": false,
  "message": "Recurso no encontrado"
}
```

**500 Internal Server Error:**
```json
{
  "success": false,
  "message": "Error interno del servidor"
}
```

---

**Versión:** 1.0.0  
**Última actualización:** Noviembre 2025  
**Colección Postman:** Disponible en `/docs/postman_collection.json`