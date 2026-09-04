# MVP CLOUDFLARE — Lista de tareas priorizadas
**Fecha:** 2026-04-01
**Objetivo:** App 100% en Cloudflare, sin depender de la PC de Hugo.
**Regla:** El admin PHP/Laravel sigue funcionando en paralelo durante toda la migración.

---

## PRINCIPIO DE COEXISTENCIA

Durante la migración, el frontend puede apuntar a DOS backends:
- `sertecapp.pendziuch.com` → Laravel actual (PC de Hugo, funciona hoy)
- `api.pendziuch.com` → Cloudflare Worker (nuevo, se va construyendo)

El switch final es cambiar una línea en el frontend: `const API = 'https://api.pendziuch.com'`
Hasta ese momento, ambos coexisten sin problema.

---

## SESIÓN 2 — Infraestructura D1 + Datos (2-3 hs)
**Resultado:** Base de datos en Cloudflare con todos los datos actuales.

- [ ] Crear D1: `npx wrangler d1 create sertecapp-db`
- [ ] Actualizar database_id en wrangler.toml
- [ ] Crear script clean_export.js (filtra tablas Laravel, convierte passwords $2y$→$2b$)
- [ ] Exportar SQLite actual: `sqlite3 database.sqlite .dump > export_full.sql`
- [ ] Limpiar y aplicar a D1 local
- [ ] Verificar: 311 clientes, 394 repuestos, 5 usuarios, órdenes
- [ ] Aplicar schema + seed roles
- [ ] Commit: "feat: D1 con datos migrados"

---

## SESIÓN 3 — Worker funcionando local (2-3 hs)
**Resultado:** `npx wrangler dev --local` responde igual que Laravel.

- [ ] Probar login: `curl -X POST localhost:8787/api/login`
- [ ] Probar /api/work-orders, /api/customers, /api/parts
- [ ] Probar crear parte técnico POST /api/partes
- [ ] Ajustar cualquier diferencia de formato de respuesta vs Laravel
- [ ] Commit por cada endpoint que funciona

---

## SESIÓN 4 — Deploy Worker + Conectar Frontend (2 hs)
**Resultado:** Frontend en Cloudflare Pages apuntando al Worker. Sin túnel.

- [ ] Deploy Worker: `npx wrangler deploy`
- [ ] Configurar secret JWT: `npx wrangler secret put JWT_SECRET`
- [ ] Aplicar schema + datos a D1 producción (no local)
- [ ] Cambiar API_URL en sertecapp-tecnicos de sertecapp.pendziuch.com → Worker URL
- [ ] Probar login, órdenes, parte — todo el flujo
- [ ] Deploy frontend: deploy.bat
- [ ] Apuntar api.pendziuch.com al Worker (DNS Cloudflare)
- [ ] Commit: "feat: frontend conectado a Worker, sin túnel"

---

## SESIÓN 5 — Admin mínimo en el Worker (3-4 hs)
**Resultado:** Lo esencial del admin PHP migrado al frontend PWA.
El admin PHP sigue funcionando — esto es adicional, no reemplaza.

### Gestión de usuarios (imprescindible)
- [ ] GET /api/users — lista con roles ← ya existe en el Worker
- [ ] POST /api/users — crear técnico nuevo
- [ ] PUT /api/users/:id — editar nombre, email, teléfono
- [ ] POST /api/users/:id/reset-password — resetear contraseña
- [ ] DELETE (soft) /api/users/:id — desactivar usuario
- [ ] Pantalla /admin/usuarios en el frontend

### Gestión de clientes (imprescindible)
- [ ] POST /api/customers — crear cliente
- [ ] PUT /api/customers/:id — editar cliente
- [ ] Pantalla /admin/clientes con búsqueda y paginación
- [ ] Formulario crear/editar cliente (inline, sin nueva página)

### Gestión de equipos (imprescindible)
- [ ] POST /api/equipments — crear equipo y asignarlo a cliente
- [ ] PUT /api/equipments/:id — editar equipo
- [ ] Pantalla /admin/equipos filtrable por cliente

### Ver partes completados (imprescindible para Luis)
- [ ] GET /api/partes/:orden_id — ya existe en el Worker
- [ ] En /admin, al abrir una orden completada, mostrar diagnóstico + firma
- [ ] Botón "Ver Parte" en la lista de órdenes del admin

---

## SESIÓN 6 — Importación de Excel (necesario para el día a día)
**Resultado:** Admin puede importar clientes y repuestos desde Excel sin tocar PHP.

### Importar clientes desde Excel
- [ ] Ruta POST /api/import/customers en el Worker
- [ ] Acepta JSON (el frontend parsea el Excel con SheetJS)
- [ ] Valida duplicados por email/tax_id antes de insertar
- [ ] Pantalla /admin/importar con drag & drop del archivo
- [ ] Preview de los registros antes de confirmar importación
- [ ] Reporte de resultado: X importados, Y duplicados, Z errores

### Importar repuestos desde Excel
- [ ] Ruta POST /api/import/parts en el Worker
- [ ] Mismo flujo que clientes
- [ ] Mapeo de columnas: part_number, name, sku, unit_cost, stock_qty

---

## SESIÓN 7 — Verificación final y switch definitivo (1-2 hs)
**Resultado:** Todo funciona sin el túnel, sin la PC de Hugo.

- [ ] Checklist completo con tunnel apagado:
  - [ ] Login admin y técnico
  - [ ] Crear orden, asignar técnico
  - [ ] Técnico ve su orden, crea parte con firma
  - [ ] Admin ve el parte completado
  - [ ] Stats del dashboard correctas
  - [ ] Importar un Excel de prueba
  - [ ] Offline: crear parte sin red → sincroniza al reconectar
- [ ] Apuntar pro.pendziuch.com a Cloudflare Pages
- [ ] Apuntar api.pendziuch.com al Worker
- [ ] Tag git: v2.0.0
- [ ] Apagar el túnel definitivamente

---

## LO QUE QUEDA PARA DESPUÉS (no bloquea el MVP)

- Tango (identificar versión → definir integración → Sesión 8+)
- Presupuestos con PDF (Sesión 9)
- Notificaciones push (cuando el técnico completa un parte)
- Reportes / dashboards más ricos
- App móvil nativa (v3.0.0)

---

## NOTAS TÉCNICAS

### Por qué el admin PHP puede seguir andando
- El Worker tiene su propia DB (D1) — no comparte SQLite con Laravel
- Si Luis necesita algo del Filament mientras migramos, sigue disponible
- El switch final es solo cambiar la URL del API en el frontend
- Podemos sincronizar datos de Laravel → D1 con un script si hace falta

### Formato respuestas — diferencias Laravel vs Worker a resolver
- Laravel devuelve `success: true/false` en partes — Worker devuelve directamente data
- Status en inglés (completed/pending) — Worker ya normaliza a español
- Paginación: meta.total, meta.per_page — Worker usa el mismo formato
