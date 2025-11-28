# 🔧 Taller - Equipos en Servicio Técnico

Endpoints para gestionar equipos en taller (servicio técnico).

---

## 📋 Tabla de Contenidos

- [GET /taller](#get-taller) - Listar equipos en taller
- [GET /taller/:id](#get-tallerid) - Obtener equipo específico
- [POST /taller](#post-taller) - Ingresar equipo al taller
- [PUT /taller/:id](#put-tallerid) - Actualizar equipo
- [DELETE /taller/:id](#delete-tallerid) - Eliminar registro
- [POST /taller/:id/asignar-tecnico](#post-tallerid-asignar-tecnico) - Asignar técnico
- [POST /taller/:id/cambiar-estado](#post-tallerid-cambiar-estado) - Cambiar estado
- [GET /taller/estadisticas/por-tecnico](#get-taller-estadisticas-por-tecnico) - Estadísticas
- [GET /taller/pendientes](#get-taller-pendientes) - Equipos pendientes

---

## GET /taller

Listar equipos en taller con filtros.

### Request

```http
GET /api/taller?page=1&per_page=15&estado=en_reparacion&tecnico_id=2
Authorization: Bearer {token}
```

### Query Parameters

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `page` | integer | Número de página | `page=2` |
| `per_page` | integer | Items por página | `per_page=20` |
| `estado` | string | Filtrar por estado | `ingresado`, `en_reparacion`, `esperando_repuesto`, `listo`, `entregado` |
| `tecnico_id` | integer | Filtrar por técnico | `tecnico_id=2` |

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "cliente_id": 5,
        "cliente_nombre": "Fitness Center",
        "cliente_telefono": "011-5555-6666",
        "equipo": "Cinta de correr",
        "marca": "Life Fitness",
        "modelo": "T5",
        "numero_serie": "LF-T5-123456",
        "problema_reportado": "No enciende display",
        "diagnostico": "Placa controladora dañada",
        "solucion": "Reemplazo de placa principal",
        "fecha_ingreso": "2025-11-20",
        "fecha_estimada": "2025-11-27",
        "fecha_salida": null,
        "estado": "en_reparacion",
        "tecnico_id": 2,
        "tecnico_nombre": "Juan Técnico",
        "dias_en_taller": 7,
        "observaciones": "Requiere repuesto importado",
        "created_at": "2025-11-20 09:00:00",
        "updated_at": "2025-11-25 14:30:00"
      }
    ],
    "pagination": {
      "total": 15,
      "per_page": 15,
      "current_page": 1,
      "last_page": 1
    }
  }
}
```

---

## 📊 Estados de Equipo

| Estado | Descripción | Color |
|--------|-------------|-------|
| `ingresado` | Recién ingresado, sin asignar | Gris |
| `en_reparacion` | Técnico trabajando | Amarillo |
| `esperando_repuesto` | Esperando repuesto | Naranja |
| `listo` | Reparado, listo para entregar | Verde |
| `entregado` | Entregado al cliente | Azul |

---

**Estado:** ✅ Implementado  
**Endpoints totales:** 9  
**Última actualización:** Noviembre 27, 2025

---

**Ver documentación completa de cada endpoint en el código fuente.**
