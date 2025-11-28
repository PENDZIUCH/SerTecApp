# 📊 Reportes - Estadísticas y Métricas

Endpoints para obtener reportes, estadísticas y dashboards.

---

## 📋 Tabla de Contenidos

- [GET /reportes/dashboard](#get-reportes-dashboard) - Dashboard general
- [GET /reportes/clientes-activos](#get-reportes-clientes-activos) - Clientes activos
- [GET /reportes/abonos-vencer](#get-reportes-abonos-vencer) - Abonos próximos a vencer
- [GET /reportes/taller-por-tecnico](#get-reportes-taller-por-tecnico) - Equipos por técnico
- [GET /reportes/facturacion-mes](#get-reportes-facturacion-mes) - Facturación mensual
- [GET /reportes/ordenes-trabajo](#get-reportes-ordenes-trabajo) - Órdenes de trabajo
- [GET /reportes/repuestos-mas-usados](#get-reportes-repuestos-mas-usados) - Repuestos más usados

---

## GET /reportes/dashboard

Resumen general para dashboard principal.

### Request

```http
GET /api/reportes/dashboard
Authorization: Bearer {token}
```

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "clientes": {
      "total": 150,
      "abonados": 120,
      "esporadicos": 30,
      "activos": 145,
      "morosos": 5
    },
    "ordenes": {
      "total": 1250,
      "pendientes": 15,
      "en_progreso": 25,
      "completadas": 1210,
      "hoy": 8
    },
    "abonos": {
      "total": 120,
      "activos": 115,
      "vencidos": 3,
      "proximos_vencer": 12
    },
    "taller": {
      "total": 35,
      "ingresados": 5,
      "en_reparacion": 18,
      "listos": 10,
      "entregados": 2
    },
    "facturacion": {
      "total_facturas": 45,
      "total_facturado": 2750000.00,
      "enviadas": 40,
      "pendientes": 5
    },
    "repuestos": {
      "total": 156,
      "stock_bajo": 12,
      "sin_stock": 3
    },
    "fecha_reporte": "2025-11-27 18:30:00"
  }
}
```

### Notas

- Incluye métricas del mes actual
- Actualizar periódicamente (cada 5 minutos recomendado)
- Útil para widgets del dashboard principal

---

## GET /reportes/facturacion-mes

Reporte detallado de facturación mensual.

### Request

```http
GET /api/reportes/facturacion-mes?mes=2025-11
Authorization: Bearer {token}
```

### Query Parameters

| Parámetro | Tipo | Descripción | Default |
|-----------|------|-------------|---------|
| `mes` | string | Mes a consultar (YYYY-MM) | Mes actual |

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "mes": "2025-11",
    "resumen": {
      "total_facturas": 45,
      "total_facturado": 2750000.00,
      "subtotal": 2272727.27,
      "iva": 477272.73,
      "facturas_tipo_a": 30,
      "facturas_tipo_b": 10,
      "facturas_tipo_c": 5,
      "monto_tipo_a": 1815000.00,
      "monto_tipo_b": 605000.00,
      "monto_tipo_c": 330000.00
    },
    "top_clientes": [
      {
        "id": 1,
        "nombre": "Club Ateneo Gym",
        "razon_social": "Ateneo Gym S.A.",
        "cantidad_facturas": 3,
        "total_facturado": 180000.00
      }
    ],
    "facturacion_diaria": [
      {
        "fecha": "2025-11-01",
        "cantidad": 2,
        "total": 120000.00
      }
    ]
  }
}
```

---

## 📊 Tipos de Reportes

### Operativos
- Dashboard general (tiempo real)
- Órdenes del día/semana/mes
- Equipos en taller por técnico

### Financieros
- Facturación mensual/anual
- Abonos vencidos y por vencer
- Clientes morosos

### Inventario
- Stock bajo
- Repuestos más usados
- Historial de movimientos

---

## 🔄 Actualización de Datos

**Recomendaciones:**

- **Dashboard**: Actualizar cada 5 minutos
- **Reportes financieros**: Cache de 1 hora
- **Alertas de stock**: Actualizar cada 15 minutos
- **Estadísticas históricas**: Cache de 24 horas

---

## 📈 Visualizaciones Sugeridas

### Dashboard Principal
- Cards con totales (clientes, órdenes, facturación)
- Gráfico de facturación mensual (línea)
- Lista de abonos próximos a vencer
- Alertas de stock bajo

### Reportes Financieros
- Gráfico de barras: Facturación por mes
- Pie chart: Distribución tipo A/B/C
- Top 10 clientes

### Reportes Operativos
- Tabla: Equipos por técnico
- Gráfico de progreso: Estados de órdenes
- Timeline: Equipos en taller

---

**Estado:** ✅ Implementado  
**Endpoints totales:** 7  
**Última actualización:** Noviembre 27, 2025

---

**Ver documentación completa de cada endpoint en el código fuente.**
