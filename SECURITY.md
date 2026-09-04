# SerTecApp — Estado de seguridad

> Última auditoría: 2026-09-04. Detalle completo del proceso y hallazgos en `CLAUDE.md` (secciones "Sesión 2026-09-04"). Este archivo es el resumen de referencia rápida — para contexto, historial y decisiones, ver `CLAUDE.md`.

## Cómo se verificó

Auditoría de 3 agentes en paralelo (backend/Filament, API REST, PWA Next.js) sobre el código real, seguida de verificación contra producción (`demo.pendziuch.com`) vía SSH/tinker de solo lectura antes y después de cada fix — no es una revisión solo-de-código, cada fix crítico se confirmó con datos reales.

## Cerrado (16 hallazgos → 15 resueltos, 1 con decisión de producto pendiente)

| # | Hallazgo | Severidad | Estado |
|---|---|---|---|
| 1 | `magic-link/generate` público, daba token de acceso total a cualquier `user_id` | Crítico | ✅ Requiere auth + rol admin-tier |
| 2 | Endpoints de técnico (`ordenes/tecnico`, `partes`) 100% públicos | Crítico | ✅ Requiere auth + verificación de identidad |
| 3 | IDOR sistémico: cualquier token tocaba cualquier recurso de la API | Crítico | ✅ 8 Policies reescritas + `authorizeResource()` |
| 4 | Páginas de configuración (SMTP, general, módulos) sin proteger la ruta real | Crítico | ✅ `canAccess()` en las 3 + 3 más encontradas en el barrido (`PdfTemplate`/`SystemLog`/`SystemSetting`) |
| 5 | Cualquier usuario logueado descargaba cualquier PDF de presupuesto | Crítico | ✅ Chequeo de rol admin-tier |
| 6 | `UserController` API sin protección en list/delete | Alto | ✅ Guard admin-tier explícito |
| 7 | FormRequests escritos pero nunca usados (bypass de validación/permiso) | Alto | ✅ Wireados a los controllers |
| 8 | Sin revocación de tokens al desactivar un usuario | Alto | ✅ Hook en el modelo `User` |
| 9 | Técnico podía autoaprobar su propio parte de trabajo | Alto | ✅ Chequeo de rol en `visible()` |
| 10 | `NotificationResource` sin ninguna protección (barrido posterior) | Alto | ✅ Scoping por usuario + `canCreate` restringido |
| 11 | CORS con wildcard `*.pages.dev` (dominio compartido) | Medio | ✅ Acotado al proyecto propio |
| 12 | Policies de Filament con permisos que nunca existieron (fallaban cerrado, pero desconectadas) | Medio | ✅ Reescritas con `hasAnyRole()` — el rediseño completo Roles↔Permisos sigue pendiente, ver abajo |
| 13 | Service Worker cacheaba contra el dominio equivocado (typo) | Medio | ✅ Corregido + purga de caché en logout |
| 14 | Auto-escalación a `super_admin` vía Filament (dropdown de rol sin filtrar) | Crítico | ✅ 3 capas: opciones filtradas, validación server-side, helper reusado |
| 15 | El mismo hueco de auto-escalación, abierto también por la API directa | Crítico | ✅ `StoreUserRequest`/`UpdateUserRequest` con la misma regla |
| 16 | Magic link de larga duración (30-365 días) en texto plano por WhatsApp/email | Bajo/Medio | ⏳ **Pendiente** — cambia el flujo de UX, se decide con Hugo antes de tocar |

## Cobertura de regresión

24 tests automatizados (154 aserciones) en `backend-laravel/tests/Feature/` fijan el comportamiento de los fixes críticos y altos — correr con `php artisan test tests/Feature/<Archivo>.php` (no con `php artisan test` a secas, ver nota sobre Pest roto en `CLAUDE.md`). `test-sertecapp.bat` en la raíz hace un chequeo de humo contra producción en un solo comando.

## Backlog real (conocido, documentado, no oculto)

1. **Magic link en texto plano** (hallazgo #16) — decisión de producto pendiente con Hugo.
2. **Rediseño Roles↔Permisos**: el panel "Roles" de Filament sigue sin controlar de verdad el acceso (las Policies usan roles hardcodeados, no los permisos que se editan ahí). Se hace junto con la limpieza de `PermissionsSeeder.php` (asigna permisos a roles en inglés que no son los reales en producción).
3. **`NotificationResource`/`App\Models\Notification`**: código muerto, no coincide con el esquema real de la tabla. Decidir: borrar o arreglar el esquema.
4. **Dependencias**: Composer/npm no auditados en esta pasada (fuera de alcance). `pestphp/pest` referenciado en tests pero nunca instalado — decisión de Hugo si se agrega.

## Lo que esto NO es

Una auditoría enfocada de una sesión, por más rigurosa que haya sido la verificación, no es un pentest formal ni cubre infraestructura (Hostinger, SSH, rate limiting) ni supply chain (dependencias). Para datos más sensibles que los actuales, un pentest externo pago sigue siendo lo correcto antes de asumir cobertura total.
