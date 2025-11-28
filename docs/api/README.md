# 📚 SerTecApp API Documentation

Documentación completa de la API REST de SerTecApp.

---

## 📖 Índice de Documentación

### 🔹 Información General
- **[Overview](./overview.md)** - Guía general de la API, autenticación, paginación, convenciones

### 🔹 Módulos de la API

| Módulo | Descripción | Archivo |
|--------|-------------|---------|
| **Auth** | Login, tokens, password reset | [auth.md](./auth.md) |
| **Clientes** | Gestión de clientes | [clientes.md](./clientes.md) |
| **Órdenes** | Órdenes de trabajo | [ordenes.md](./ordenes.md) |
| **Abonos** | Suscripciones mensuales | [abonos.md](./abonos.md) |
| **Repuestos** | Inventario y stock | [repuestos.md](./repuestos.md) |
| **Taller** | Equipos en servicio | [taller.md](./taller.md) |
| **Facturación** | Comprobantes y Tango | [facturacion.md](./facturacion.md) |
| **Reportes** | Estadísticas y métricas | [reportes.md](./reportes.md) |

---

## 🚀 Quick Start

### 1. Autenticación

```bash
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "email": "admin@sertecapp.com",
  "password": "admin123"
}
```

### 2. Usar Token en Requests

```bash
GET http://localhost:8000/api/clientes
Authorization: Bearer {tu_token_jwt}
```

---

## 📊 Endpoints Totales

- **Auth:** 7 endpoints
- **Clientes:** 5 endpoints
- **Órdenes:** 5 endpoints
- **Abonos:** 7 endpoints
- **Repuestos:** 8 endpoints
- **Taller:** 9 endpoints
- **Facturación:** 8 endpoints
- **Reportes:** 7 endpoints

**Total:** 56 endpoints implementados

---

## 🛠️ Base URLs

- **Desarrollo:** `http://localhost:8000/api`
- **Producción:** `https://api.sertecapp.com/api`

---

## 📝 Formato de Respuestas

Todas las respuestas usan el formato estándar:

```json
{
  "success": true,
  "data": { ... },
  "message": "..."
}
```

---

## 🔐 Seguridad

- ✅ JWT con HS256
- ✅ Tokens expiran en 24 horas
- ✅ Refresh tokens disponibles
- ✅ CORS configurado
- ✅ Rate limiting en producción

---

## 📚 Recursos Adicionales

- [Postman Collection](../POSTMAN.md) - Importar colección completa
- [Database Schema](../../database/schema.sql) - Esquema de BD
- [Deployment Guide](../DEPLOYMENT.md) - Guía de deployment

---

**Estado:** ✅ Documentación completa  
**Versión:** 1.0.0  
**Última actualización:** Noviembre 27, 2025
