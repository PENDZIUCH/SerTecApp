# Comparativa: Filament Admin vs SerTecApp Admin

Documentación de qué funcionalidades existen en cada plataforma para decidir qué integrar en la app.

**Fecha:** 2026-04-09  
**Rama:** development (local MySQL)

---

## 📋 Recursos/Módulos

### Filament (14 recursos)

| Recurso | Operaciones | Estado |
|---------|-----------|--------|
| **WorkOrder** | CRUD, cambiar estado, agregar partes | ✅ Completo |
| **Customer** | CRUD, búsqueda | ✅ Completo |
| **Equipment** | CRUD, cambiar estado | ✅ Completo |
| **User** | CRUD, gestión de roles/permisos | ✅ Completo |
| **Part** | CRUD, registrar movimientos | ✅ Completo |
| **Budget** | CRUD, aprobar/rechazar | ✅ Completo |
| **Visit** | CRUD, check-in/check-out | ✅ Completo |
| **Subscription** | CRUD, renovar | ✅ Completo |
| **WorkshopItem** | CRUD (inventario taller) | ✅ Completo |
| **WorkPart** | CRUD (partes usadas en órdenes) | ✅ Completo |
| **Notification** | Listar, ver | ✅ Completo |
| **PdfTemplate** | CRUD | ✅ Completo |
| **SystemSetting** | Configuración global | ✅ Completo |
| **SystemLog** | Auditoria, historial | ✅ Completo |

---

## 🚀 SerTecApp Admin (en /admin)

| Funcionalidad | Operación | Estado | Notas |
|---------------|-----------|--------|-------|
| **Work Orders** | Crear | ✅ Implementado | Modal con form, asigna técnico |
| **Work Orders** | Listar | ✅ Implementado | Lista básica, 50 items paginados |
| **Work Orders** | Ver detalle | ❌ No | Click va a `/admin/orden?id=X` |
| **Work Orders** | Editar | ❌ No | No hay formulario de edición |
| **Work Orders** | Cambiar estado | ❌ No | Falta UI para cambiar pendiente→en_progreso→completado |
| **Work Orders** | Eliminar | ❌ No | No está implementado |
| **Work Orders** | Agregar partes | ❌ No | API existe, pero no hay UI |
| **Customer** | Listar | ✅ Parcial | Va a `/admin/clientes` |
| **Customer** | Crear | ❌ No | No hay form de creación |
| **Customer** | Editar | ❌ No | Solo lectura en `/admin/clientes` |
| **Customer** | Importar Excel | ✅ Implementado | Va a `/admin/importar` |
| **User** | Listar | ✅ Implementado | Va a `/admin/gestion` |
| **User** | Crear | ❌ Parcial | Probablemente en `/admin/gestion` |
| **User** | Editar | ❌ Parcial | Probablemente en `/admin/gestion` |
| **Vista Técnico** | Simular | ✅ Implementado | Ver órdenes como si fuera técnico |
| **Equipment** | Listar | ✅ Implementado | En modal de crear orden |
| **Equipment** | Crear | ❌ No | No hay UI |
| **Part** | Listar | ✅ Implementado | Solo en estadísticas |
| **Part** | Crear | ❌ No | Probablemente en `/admin/importar` |
| **Budget** | CRUD | ❌ No | No existe en app |
| **Visit** | CRUD | ❌ No | No existe en app |
| **Subscription** | CRUD | ❌ No | No existe en app |
| **Notification** | Ver | ❌ No | No existe en app |
| **WorkshopItem** | Inventario | ❌ No | No existe en app |
| **SystemSetting** | Config | ❌ No | No existe en app |
| **SystemLog** | Auditoria | ❌ No | No existe en app |

---

## 🎯 Lo CRÍTICO para que funcione primero

Estos son los features necesarios ANTES de testear Excel import:

| Feature | Filament ✅ | App ❌ | Prioridad |
|---------|-------------|---------|-----------|
| Ver detalle de orden | Sí | No | 🔴 ALTA |
| Editar orden | Sí | No | 🔴 ALTA |
| Cambiar estado orden | Sí | No | 🔴 ALTA |
| Crear presupuesto | Sí | No | 🟡 MEDIA |
| Ver partes usadas | Sí | Parcial | 🟡 MEDIA |
| Registrar visita | Sí | No | 🟢 BAJA |
| Ver notificaciones | Sí | No | 🟢 BAJA |

---

## 📡 Endpoints API Disponibles (todos en `/api/v1/`)

**IMPLEMENTADOS y accesibles desde app:**
- ✅ POST `/login` — autenticación
- ✅ GET `/work-orders` — listar órdenes
- ✅ POST `/work-orders` — crear orden
- ✅ GET `/work-orders/{id}` — detalle
- ✅ PATCH `/work-orders/{id}` — editar
- ✅ DELETE `/work-orders/{id}` — eliminar
- ✅ POST `/work-orders/{id}/change-status` — cambiar estado
- ✅ POST `/work-orders/{id}/parts` — agregar partes
- ✅ GET `/customers` — listar clientes
- ✅ POST `/customers` — crear cliente
- ✅ PATCH `/customers/{id}` — editar
- ✅ GET `/equipments` — listar equipos
- ✅ POST `/equipments` — crear equipo
- ✅ GET `/users` — listar usuarios
- ✅ POST `/users` — crear usuario
- ✅ PATCH `/users/{id}` — editar
- ✅ GET `/parts` — listar repuestos
- ✅ POST `/parts` — crear repuesto
- ✅ GET `/visits` — listar visitas
- ✅ POST `/visits` — crear visita
- ✅ Y más...

---

## 🔗 Estructura de Rutas App

```
/admin                     — Dashboard principal (estadísticas, crear orden)
/admin/gestion            — Gestión de usuarios
/admin/clientes           — Listar clientes
/admin/orden?id=X         — Detalle de orden (VACÍO - falta implementar)
/admin/importar           — Importar Excel clientes/repuestos
```

---

## 📝 Notas

1. **BD:** Ambos (Filament + App) leen/escriben en la misma MySQL local
2. **Autenticación:** La app usa tokens Bearer. Filament usa sesión Filament.
3. **Real-time:** NO hay updates en tiempo real (falta polling/WebSocket)
4. **Responsive:** App es mobile-first, Filament es desktop

---

## Próximos Pasos (cuando hayas probado que funciona el login)

1. Implementar `/admin/orden` — ver detalle completo
2. Agregar edición de orden en el detalle
3. Cambiar estado (botones: Pendiente → En Progreso → Completado)
4. Registrar partes usadas en la orden
5. (Opcional) Presupuestos, visitas, notificaciones
