# Fix Hostinger 2026-04-14 — Login 403 Error

## Problema Identificado

**URL:** [https://demos.pendziuch.com/admin/login](https://demos.pendziuch.com/admin/login)

**Error:** HTTP 403 al intentar hacer POST de login

**Causa:** Claude Code había creado dos middlewares con errores de sintaxis PHP:
- `app/Http/Middleware/FixClientIp.php:10` → `syntax error, unexpected token ",", expecting variable`
- `app/Http/Middleware/TrustProxiedRequests.php:10` → mismo error

Estos errores rompían la carga de Laravel completamente.

---

## Solución Aplicada (2026-04-14 15:40 UTC)

### 1. Eliminar archivos rotos
```bash
rm -f /home/u283281385/domains/demos.pendziuch.com/public_html/backend-laravel/app/Http/Middleware/FixClientIp.php
rm -f /home/u283281385/domains/demos.pendziuch.com/public_html/backend-laravel/app/Http/Middleware/TrustProxiedRequests.php
```

✅ Completado

### 2. Limpiar cache de Laravel
```bash
cd /home/u283281385/domains/demos.pendziuch.com/public_html/backend-laravel
php artisan config:cache
php artisan cache:clear
```

✅ Completado

### 3. Configuración faltante en `.env`

Se agregaron parámetros de SESSION y CSRF que faltaban (del plan CSRF_FIX_HOSTINGER.md):

```env
SESSION_COOKIE_PATH=/
SESSION_COOKIE_SECURE=true
SESSION_COOKIE_HTTP_ONLY=true
SESSION_COOKIE_SAME_SITE=lax

CSRF_COOKIE_PATH=/
CSRF_COOKIE_SECURE=true

TRUSTED_PROXIES=*
```

✅ Completado

---

## Verificación Pendiente

- [ ] Probar login en [https://demos.pendziuch.com/admin/login](https://demos.pendziuch.com/admin/login)
- [ ] Verificar que local sigue funcionando en [http://localhost:8000/admin/login](http://localhost:8000/admin/login)
- [ ] Confirmar que PWA local conecta correctamente a API local
- [ ] Verificar que la rama `development` está sincronizada

---

## Notas

- **Local no fue modificado** — Solo se trabajó en Hostinger vía SSH
- Los middlewares rotos sólo existían en Hostinger, no en el repo local
- La rama `development` está limpia y lista para próximos cambios
- Si vuelve a fallar, revisar: logs en `/storage/logs/laravel.log` en Hostinger

---

**Estado:** Waiting for verification  
**Fecha:** 2026-04-14  
**Responsable:** Claude Assistant