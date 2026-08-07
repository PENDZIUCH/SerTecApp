# SertecApp — Architecture Document

**Versión:** v0.3-stable  
**Fecha:** 2026-08-07  
**Estado:** Producción estable — inicio de fase de modularización

---

## Visión del producto

SertecApp nació como sistema de gestión de servicios técnicos para Fitness Company.  
La arquitectura está diseñada para evolucionar hacia una **plataforma modular de gestión operacional** (Field Service Management) reutilizable para múltiples clientes verticales.

El principio rector: **el core es genérico, lo particular de cada cliente es configuración**.

---

## Stack actual

| Capa | Tecnología | Ubicación |
|---|---|---|
| Admin panel | Laravel 11 + Filament 3.2 | Hostinger (demo.pendziuch.com/sertecapp) |
| Roles y permisos | Spatie Laravel Permission + Shield | backend-laravel |
| Autenticación API | Laravel Sanctum + Magic Links | backend-laravel |
| PWA técnicos | Next.js 14 (App Router) | Cloudflare Pages (sertecapp.pendziuch.com) |
| Base de datos prod | MySQL (Hostinger) | Hostinger |
| Base de datos local | SQLite | desarrollo local |
| CI/CD | GitHub webhook -> deploy-sertecapp.sh | Hostinger |

---

## Estructura del backend

```
app/
├── Enums/              <- tipos y estados del dominio (genéricos)
├── Filament/           <- INTERFAZ SOLAMENTE (no lógica de negocio)
│   ├── Pages/          <- páginas custom del admin
│   ├── Resources/      <- CRUD + acciones del admin
│   └── Widgets/        <- widgets del dashboard
├── Http/
│   ├── Controllers/    <- API REST (llaman Services)
│   ├── Requests/       <- validación HTTP
│   └── Resources/      <- transformación JSON (API Resources)
├── Mail/               <- Mailables independientes de Filament
├── Models/             <- Eloquent models con relaciones
├── Observers/          <- side effects automáticos por evento
├── Policies/           <- autorización (independiente de Filament)
├── Services/           <- LÓGICA DE NEGOCIO (el núcleo)
└── Traits/             <- comportamientos reutilizables
```

---

## Principio de capas

```
[Filament Resource]  ->  llama  ->  [Service]  ->  llama  ->  [Model]
[API Controller]     ->  llama  ->  [Service]  ->  llama  ->  [Model]
[futuro panel PWA]   ->  llama  ->  [Service]  ->  llama  ->  [Model]
```

Cualquier interfaz nueva (panel Next.js, app mobile, otro admin) consume los mismos Services sin duplicar lógica.

---

## Módulos del sistema

### Módulos actuales

| Módulo | Service | Estado |
|---|---|---|
| Órdenes de trabajo | WorkOrderService | Activo |
| Partes de trabajo | WorkPartService | Pendiente de refactor (v0.4) |
| Clientes | CustomerService | Activo |
| Autenticación | AuthService | Activo |
| Presupuestos | BudgetService | Activo |
| Visitas / Agenda | VisitService | Activo (sin UI aún) |
| Taller / Stock | WorkshopService | Activo |
| Suscripciones | SubscriptionService | Activo |
| Equipamiento | EquipmentService | Activo |
| Repuestos | PartService | Activo |
| Usuarios | UserService | Activo |
| Configuración global | SystemSetting (model) | Activo |
| Notificaciones | DB Notifications + Mail | Activo |

### Módulos planificados (futuro)

| Módulo | Descripción |
|---|---|
| Agenda / Turnos | Scheduling genérico activable sin field service |
| Fotos en partes | Captura y subida de imágenes desde la PWA |
| Firma + datos firmante | Nombre, cargo, DNI del firmante en partes |
| Fechas propuestas al cliente | Coordinación de visita antes de asignar técnico |
| instance_modules | Control de módulos activos por instancia/cliente |

---

## Deuda técnica conocida (pre v0.4)

### Crítica
- **Lógica de negocio en Filament Resources**: las acciones Aprobar/Rechazar de WorkPartResource y ViewWorkPart contienen transacciones DB, cambios de estado, notificaciones y emails directamente en el ->action() de Filament. Debe migrarse a WorkPartService.

### No crítica
- **Doble path en el repo** (app/ para Hostinger, backend-laravel/app/ para local): workaround con robocopy en deploy.bat. A resolver al migrar a VPS o Docker.
- **QUEUE_CONNECTION=sync**: Hostinger compartido no permite queue workers. El deploy script lo fuerza. Migrar a VPS habilita colas reales.
- **Sin tests automatizados**: cada cambio requiere prueba manual.
- **SQLite local vs MySQL producción**: diferencias menores posibles en queries complejos.

---

## Flujo de aprobación de partes (estado actual)

```
Técnico (PWA)
  -> POST /api/v1/partes  ->  crea WorkPart {status: pending_approval}

Supervisor (Filament)
  -> Ve WorkPart en "Partes Pendientes"
  -> Aprobar -> WorkPart {approved} + WorkOrder {completed} + notif técnico
  -> Rechazar -> WorkPart {rejected} + WorkOrder {pending} + reasigna técnico
              + notificación DB al técnico
              + email al cliente (ParteRechazadoMail)

Técnico (PWA)
  -> Ve orden con banner de rechazo + nota del supervisor
  -> Corrige y reenvía -> nuevo WorkPart (historial preservado)
```

---

## Emails implementados

| Mailable | Evento | Destinatario | Estado |
|---|---|---|---|
| OrdenCreadaMail | Crear orden | Cliente | Verificado |
| ParteCompletadoMail | Técnico envía parte | Cliente | Verificado |
| ParteRechazadoMail | Supervisor rechaza parte | Cliente | Verificado |
| AccesoUsuarioMail | Admin envía acceso | Usuario | Verificado |
| Password Reset | Usuario solicita reset | Usuario | Verificado |

SMTP: smtp.hostinger.com:465 SSL — cuenta mail@pendziuch.com  
CRITICO: QUEUE_CONNECTION=sync (el deploy script lo fuerza automáticamente)

---

## Autenticación

- Admin (Filament): email + password + reset por email
- Técnicos (PWA): Magic Link (token Sanctum 30 días) + password opcional
- Roles: super_admin, admin, técnico — gestionados con Spatie Permission

---

## CI/CD

```
deploy.bat "mensaje"
  -> git add + commit + push -> GitHub (rama: development)
  -> webhook -> Hostinger
  -> deploy-sertecapp.sh
        -> git archive (extrae exacto del remote)
        -> restaura .env de Hostinger
        -> fuerza QUEUE_CONNECTION=sync
        -> fuerza MAIL_MAILER=smtp
        -> composer install --no-dev
        -> php artisan migrate --force
```

Tags de rollback estables: v0.1-stable, v0.2-stable, v0.3-stable

---

## Roadmap de fases

### Fase 0 — Commit de seguridad (hoy)
Tag v0.3-stable. Documentación de arquitectura. Base limpia antes de refactor.

### Fase 1 — Desacoplar lógica del campo de Filament
Crear WorkPartService::approve() y WorkPartService::reject().
Filament y la API consumen el mismo service.
Regla: ningún feature nuevo hasta que esto esté.

### Fase 2 — Separar core de dominio Fitness
Identificar qué es genérico vs específico del cliente.
Implementar instance_modules para activación por cliente.

### Fase 3 — Primer cliente nuevo sobre el core
Levantar instancia nueva en menos de un día. Test real del framework.

### Fase 4 — PWA unificada (admin + técnico + supervisor)
Panel admin en Next.js consumiendo la API REST existente.
Filament queda como back-office técnico o se elimina.
Un solo codebase, instalable, offline, cualquier dispositivo.

---

## Convenciones del proyecto

- Nunca deployar sin probar en local primero
- Nunca deployar sin confirmación explícita
- Todo cambio de arquitectura empieza por el Service, no por el Resource
- Los scripts temporales fix_*.php se eliminan antes de cada commit
- Las variables críticas del .env las gestiona el deploy script, no el código
