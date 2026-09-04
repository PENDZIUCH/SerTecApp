# backend-only vs development — Análisis Comparativo

## backend-only (Demo que funcionaba en Hostinger)

**Estado:** Commit `b382706` - Backend simple, sin administración de usuarios

**Estructura:**
- `/backend-laravel/` — Laravel BÁSICO
- NO tiene: Filament, usuarios, roles, permisos, enums
- Backend pure: solo APIs para la app
- `.env.mysql.local` para config local
- `artisan` en git (importante para deploy)
- Minimal middleware

**Stack:**
- Laravel 11
- MySQL local
- NO Filament
- NO roles/permisos complejos
- NO administración de usuarios

---

## development (Actual — con Filament)

**Estado:** Commit `9475246` - Backend con Filament admin completo

**Estructura agregada vs backend-only:**
```
app/
  ├── Enums/ (16 enums nuevos)
  ├── Filament/ (Recuros admin, Importers, Widgets)
  ├── Http/Controllers/Api/V1/ (nuevos controllers)
  ├── Models/ (modelos expandidos para Filament)
  ├── Services/ (servicios de negocio)
  ├── Traits/ (traits reutilizables)
  └── Observers/ (observadores)

database/
  ├── migrations/ (nuevas migraciones para Filament)
  └── factories/ (factories para testing)

config/
  ├── filament.php (nuevo)
  ├── livewire.php (nuevo)
  ├── filament-shield.php (nuevo)
```

**Cambios en `.env`:**
- `SESSION_DRIVER=database` (en backend-only es cookie)
- Nuevas config de Filament
- Nuevas config de Spatie Permission

---

## Por qué backend-only funcionaba y development no

**Hipótesis:**

1. **backend-only era simple** — pocas dependencias, pocas configuraciones, menos superficie de error
2. **development añadió complejidad** — Filament, Livewire, Spatie Permission, múltiples middewares
3. **El deploy se rompe porque:**
   - Falta `composer install` después de git pull en Hostinger
   - Nuevo middleware roto (`FixClientIp.php`) que creó Claude Code
   - Config de SESSION/CSRF incompleta en `.env`
   - Posible conflicto entre Las duas middlewares `TrustProxies` y el middleware roto

---

## Plan para hacer funcionar development en Hostinger

1. **NO cambiar la estructura de development** (tiene todas las features que necesitás)
2. **Estudiar cómo backend-only deployaba exitosamente** para replicar ese proceso
3. **Lecciones de backend-only:**
   - `artisan` debe estar en git
   - `.gitignore` debe permitir `/vendor` después de `composer install`
   - `.env` debe tener configuración correcta para Hostinger (no localhost)
   - Middleware debe ser limpio (sin código roto)
   - DB connection debe ser `localhost` en Hostinger

---

**Siguiente paso:** Una vez que Hostinger restaure el backup, copiar el `.env` y `.htaccess` de backend-only, aplicarlos a development, y hacer deploy limpio.