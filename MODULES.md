# SertecApp — Module Map

**Rama:** core/v1
**Fecha:** 2026-08-07
**Objetivo:** Separar el core genérico del dominio específico de cada cliente

---

## Resultado del análisis

El 85% del sistema es core generico reutilizable.
El 15% es especifico del dominio de Fitness Company (equipamiento).
La separacion es mas limpia de lo esperado.

---

## CAPA 1 — Core (siempre presente, cualquier cliente)

### Infraestructura
- Auth / Users / Roles / Permissions (Spatie)
- Magic Links (SignatureTokens + Sanctum)
- SystemSettings (configuracion por instancia)
- SystemLogs / ActivityLog
- Notifications (DB + Mail)
- PdfTemplates
- CI/CD (deploy scripts)

### Modulo: Clientes
Modelos: Customer, CustomerContact, CustomerAddress, CustomerFile, CustomerNote
Service: CustomerService
Resource: CustomerResource
Estado: 100% generico — aplica a cualquier empresa

### Modulo: Ordenes de trabajo + Partes
Modelos: WorkOrder, WorkOrderLog, WorkOrderFile, WorkPart, WoPartsUsed
Service: WorkOrderService, WorkPartService
Resource: WorkOrderResource, WorkPartResource
PWA: flujo tecnico completo (crear parte, firma, geolocalizacion, rechazo)
Estado: 100% generico — campo equipment_id en WorkOrder es nullable

### Modulo: Visitas / Agenda
Modelos: Visit
Service: VisitService
Resource: VisitResource
Estado: 100% generico — scheduling de visitas a clientes

### Modulo: Presupuestos
Modelos: Budget, BudgetItem, BudgetNote
Service: BudgetService
Resource: BudgetResource
Estado: 100% generico

### Modulo: Stock / Repuestos
Modelos: Part, PartsMovement
Service: PartService
Resource: PartResource
Estado: 100% generico — cualquier empresa con repuestos en stock

### Modulo: Taller
Modelos: WorkshopItem
Service: WorkshopService
Resource: WorkshopItemResource
Estado: 95% generico — revisar si tiene campos de fitness

### Modulo: Suscripciones
Modelos: Subscription, SubscriptionRenewalHistory
Service: SubscriptionService
Resource: SubscriptionResource
Estado: 100% generico — billing recurrente de clientes

---

## CAPA 2 — Dominio especifico (configurable por cliente)

### Modulo: Equipamiento [DOMINIO FITNESS COMPANY]
Modelos: Equipment, EquipmentBrand, EquipmentModel, EquipmentHistory, EquipmentFile
Service: EquipmentService
Resource: EquipmentResource
Vinculo con core: WorkOrder.equipment_id (nullable — ya funciona sin esto)
Estado: ESPECIFICO de empresas que gestionan activos fisicos de clientes
Reutilizable para: talleres de electrodomesticos, HVAC, fotocopiadoras, etc.
NO aplica para: agendas de turnos, consultorios, educacion

---

## Mapa de activacion por vertical

| Modulo           | Field Service | Agenda/Turnos | Taller | E-commerce |
|------------------|:---:|:---:|:---:|:---:|
| Clientes         |  S  |  S  |  S  |  S  |
| Ordenes + Partes |  S  |  -  |  S  |  -  |
| Visitas/Agenda   |  S  |  S  |  -  |  -  |
| Presupuestos     |  S  |  -  |  S  |  S  |
| Stock/Repuestos  |  S  |  -  |  S  |  -  |
| Taller           |  -  |  -  |  S  |  -  |
| Suscripciones    |  S  |  S  |  -  |  S  |
| Equipamiento     |  S  |  -  |  S  |  -  |

---

## Plan de implementacion (Fase 2)

### Paso 1 — ModuleManager (sin romper nada existente)
Crear app/Services/ModuleManager.php que lee SystemSetting.
Agregar key 'active_modules' en SystemSetting con JSON de modulos activos.
Default: todos activos (Fitness Company no nota nada).

### Paso 2 — Registro condicional en Filament
En AdminPanelProvider, cada Resource se registra solo si su modulo esta activo:
  if (ModuleManager::isActive('equipment')) { EquipmentResource }
Default: todos activos. Sin impacto en instancia actual.

### Paso 3 — Pagina de modulos en el admin
Nueva pagina solo para super_admin: activar/desactivar modulos por instancia.
Fitness Company ve todos activos. Nueva instancia puede desactivar lo que no usa.

### Paso 4 — Script de nueva instancia
install.sh que configura .env, corre migraciones, crea super_admin y
activa los modulos seleccionados. Nueva instancia en menos de 10 minutos.

### Paso 5 — Demo en demo.pendziuch.com/core
Deploy de instancia limpia para pilotear el "segundo cliente".
Rama separada, base de datos separada, dominio separado.
Fitness Company no se entera, sus datos no se tocan.

---

## Lo que NO cambia en esta fase

- La base de datos de Fitness Company no se toca
- El deploy de development no se toca
- La PWA de tecnicos no se toca
- Ningun Model, Controller ni Service existente se modifica
- Solo se agrega, nunca se elimina

---

## Estimacion de esfuerzo

| Paso | Esfuerzo | Riesgo |
|------|----------|--------|
| ModuleManager | 1h | Cero — solo lectura de config |
| Registro condicional Filament | 2h | Bajo — default todos activos |
| Pagina de modulos admin | 2h | Bajo — solo UI |
| Script de nueva instancia | 2h | Bajo — ambiente separado |
| Deploy demo instancia limpia | 1h | Bajo — rama separada |

Total estimado: 8 horas de trabajo efectivo.