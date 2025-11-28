# 📡 SerTecApp API - Overview

**Versión:** 1.0.0 (Core Backend Pendziuch v1)  
**Base URL:** `http://localhost:8000/api` (desarrollo) | `https://api.sertecapp.com/api` (producción)

---

## 🎯 Descripción General

SerTecApp API es un backend RESTful completo para la gestión de servicios técnicos de equipamiento deportivo. Permite administrar clientes, órdenes de trabajo, abonos, repuestos, taller y facturación.

---

## 🔐 Autenticación

La API usa **JWT (JSON Web Tokens)** para autenticación.

### Flujo de Autenticación

```
1. POST /auth/login → Obtener token + refresh_token
2. Guardar token en el cliente
3. Incluir token en cada request: Authorization: Bearer {token}
4. Si token expira (401) → POST /auth/refresh
5. Repetir proceso con nuevo token
```

### Headers Requeridos

```http
Content-Type: application/json
Authorization: Bearer {tu_token_jwt}
```

### Ejemplo de Header

```http
GET /api/clientes
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

---

## 📦 Formato de Respuestas

Todas las respuestas siguen el mismo formato estándar:

### Respuesta Exitosa

```json
{
  "success": true,
  "data": { ... },
  "message": "Mensaje opcional"
}
```

### Respuesta con Error

```json
{
  "success": false,
  "message": "Descripción del error",
  "errors": { ... }  // Opcional: detalles de validación
}
```

---

## 🎨 Códigos de Estado HTTP

| Código | Significado | Uso |
|--------|-------------|-----|
| `200` | OK | Request exitoso (GET, PUT, DELETE) |
| `201` | Created | Recurso creado exitosamente (POST) |
| `400` | Bad Request | Datos inválidos o faltantes |
| `401` | Unauthorized | Token faltante o inválido |
| `403` | Forbidden | Sin permisos para esta acción |
| `404` | Not Found | Recurso no encontrado |
| `422` | Unprocessable Entity | Errores de validación |
| `500` | Internal Server Error | Error del servidor |

---

## 📋 Paginación

Los endpoints que retornan listas usan paginación:

### Query Parameters

- `page` (default: 1) - Número de página
- `per_page` (default: 15) - Items por página

### Ejemplo Request

```http
GET /api/clientes?page=2&per_page=20
```

### Ejemplo Response

```json
{
  "success": true,
  "data": {
    "data": [ ... ],
    "pagination": {
      "total": 150,
      "per_page": 20,
      "current_page": 2,
      "last_page": 8
    }
  }
}
```

---

## 🔍 Filtros y Búsqueda

Muchos endpoints soportan filtros via query parameters:

```http
GET /api/clientes?tipo=abonado&estado=activo&search=gym
GET /api/ordenes?desde=2025-01-01&hasta=2025-12-31
GET /api/repuestos?stock_bajo=true
```

Consulta la documentación de cada endpoint para ver filtros disponibles.

---

## 🛡️ Roles y Permisos

### Roles Disponibles

- **admin** - Acceso completo
- **tecnico** - Acceso a órdenes, taller, repuestos
- **vendedor** - Acceso a clientes, abonos, facturación

### Restricciones

Algunos endpoints requieren rol específico:
- DELETE de recursos críticos → Solo `admin`
- Reportes financieros → `admin` o `vendedor`

---

## 🚀 Rate Limiting

**Desarrollo:** Sin límites  
**Producción:** 100 requests por minuto por IP

Si excedes el límite:

```json
{
  "success": false,
  "message": "Too many requests. Please try again later.",
  "retry_after": 60
}
```

---

## 🌍 CORS

El API permite requests desde cualquier origen en desarrollo.

**Producción:** Solo dominios autorizados en `.env`

```env
CORS_ALLOWED_ORIGINS=https://app.sertecapp.com,https://admin.sertecapp.com
```

---

## 📝 Convenciones

### Nombres de Campos

- **snake_case** en respuestas JSON
- **camelCase** aceptado en requests (se convierte automáticamente)

### Fechas

- Formato: `YYYY-MM-DD` (ej: `2025-11-27`)
- Datetime: `YYYY-MM-DD HH:MM:SS` (ej: `2025-11-27 14:30:00`)
- Timezone: `America/Argentina/Buenos_Aires`

### Moneda

- Todos los montos en **pesos argentinos (ARS)**
- Formato numérico: `12500.50` (sin separadores de miles)

---

## 📚 Módulos Disponibles

| Módulo | Descripción | Documentación |
|--------|-------------|---------------|
| Auth | Login, tokens, password reset | [auth.md](./auth.md) |
| Clientes | Gestión de clientes | [clientes.md](./clientes.md) |
| Órdenes | Órdenes de trabajo | [ordenes.md](./ordenes.md) |
| Abonos | Suscripciones mensuales | [abonos.md](./abonos.md) |
| Repuestos | Inventario | [repuestos.md](./repuestos.md) |
| Taller | Equipos en servicio | [taller.md](./taller.md) |
| Facturación | Comprobantes y Tango | [facturacion.md](./facturacion.md) |
| Reportes | Estadísticas y métricas | [reportes.md](./reportes.md) |

---

## 🧪 Testing

### Postman Collection

Descarga la colección completa: `SerTecApp.postman_collection.json`

### Variables de Entorno

```json
{
  "base_url": "http://localhost:8000/api",
  "token": "{{tu_token_jwt}}"
}
```

### Datos de Prueba

Usuario admin por defecto:
```json
{
  "email": "admin@sertecapp.com",
  "password": "admin123"
}
```

---

## 🐛 Debugging

En desarrollo, los errores incluyen stacktrace:

```json
{
  "success": false,
  "message": "Database connection failed",
  "trace": "..." // Solo en APP_DEBUG=true
}
```

**Producción:** Los errores son genéricos para no exponer información sensible.

---

## 📞 Soporte

**Documentación completa:** `/docs/api/`  
**Issues:** GitHub Issues  
**Email:** soporte@sertecapp.com

---

**Última actualización:** Noviembre 27, 2025  
**Estado:** ✅ Producción Ready
