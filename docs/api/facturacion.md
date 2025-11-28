# 💰 Facturación - Gestión de Comprobantes

Endpoints para gestionar facturación e integración con Tango Software.

---

## 📋 Tabla de Contenidos

- [GET /facturacion](#get-facturacion) - Listar facturas
- [GET /facturacion/:id](#get-facturacionid) - Obtener factura con items
- [POST /facturacion](#post-facturacion) - Crear factura
- [PUT /facturacion/:id](#put-facturacionid) - Actualizar factura
- [DELETE /facturacion/:id](#delete-facturacionid) - Anular factura
- [POST /facturacion/:id/enviar-tango](#post-facturacionid-enviar-tango) - Enviar a Tango
- [POST /facturacion/probar](#post-facturacion-probar) - Preview de factura
- [GET /facturacion/resumen-mes](#get-facturacion-resumen-mes) - Resumen mensual

---

## GET /facturacion

Listar facturas con filtros.

### Request

```http
GET /api/facturacion?page=1&cliente_id=5&desde=2025-11-01&hasta=2025-11-30
Authorization: Bearer {token}
```

### Query Parameters

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `page` | integer | Número de página | `page=2` |
| `per_page` | integer | Items por página | `per_page=20` |
| `cliente_id` | integer | Filtrar por cliente | `cliente_id=5` |
| `desde` | date | Fecha desde | `desde=2025-11-01` |
| `hasta` | date | Fecha hasta | `hasta=2025-11-30` |

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "numero": 1,
        "tipo": "A",
        "cliente_id": 1,
        "cliente_nombre": "Club Ateneo Gym",
        "cliente_razon_social": "Ateneo Gym S.A.",
        "cliente_cuit": "30-12345678-9",
        "fecha": "2025-11-27",
        "subtotal": 50000.00,
        "iva": 10500.00,
        "total": 60500.00,
        "observaciones": "Mantenimiento mensual",
        "tango_id": "TGO-20251127-1234",
        "tango_status": "enviada",
        "enviada": true,
        "created_at": "2025-11-27 10:00:00",
        "updated_at": "2025-11-27 10:05:00"
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

---

## 💼 Tipos de Factura

| Tipo | Descripción | IVA |
|------|-------------|-----|
| `A` | Factura A - Responsables Inscriptos | 21% discriminado |
| `B` | Factura B - Consumidores Finales | 21% incluido |
| `C` | Factura C - Monotributistas | Sin IVA |

---

## 🔄 Integración Tango Software

### Estados

- `pendiente` - Factura creada, no enviada a Tango
- `enviada` - Enviada y registrada en Tango
- `error` - Error al enviar (reintentar)

### Mock API

**Estado actual:** Sistema usa mock de Tango API para desarrollo.

**Para integración real:** Actualizar endpoint en `.env`:
```env
TANGO_API_URL=https://api.tango.com
TANGO_API_KEY=tu_api_key_real
TANGO_ENABLED=true
```

---

**Estado:** ✅ Implementado con mock  
**Endpoints totales:** 8  
**Última actualización:** Noviembre 27, 2025

---

**Ver documentación completa de cada endpoint en el código fuente.**
