# FIX para 419 Error en Filament/Livewire (Hostinger)

**Status:** 2026-04-13 - CRÍTICO: Aplicar en Hostinger inmediatamente

## El Problema
- Login form muestra "This page has expired" (419) cuando se intenta Submit
- El CSRF token no matchea entre la página y el envío del formulario
- Ocurre porque SESSION_COOKIE_PATH no está configurado igual que SESSION_PATH

## La Solución
En `/home/u283281385/domains/demos.pendziuch.com/public_html/sertecapp/backend-laravel/.env`, agregar estas líneas:

```env
# Session Configuration
SESSION_DRIVER=cookie
SESSION_LIFETIME=120
SESSION_PATH=/sertecapp
SESSION_DOMAIN=null
SESSION_COOKIE_PATH=/sertecapp
SESSION_COOKIE_SECURE=true
SESSION_COOKIE_HTTP_ONLY=true
SESSION_COOKIE_SAME_SITE=lax

# CSRF Token Cookie
CSRF_COOKIE_PATH=/sertecapp
CSRF_COOKIE_SECURE=true

# App URL must include the subdirectory
APP_URL=https://demos.pendziuch.com/sertecapp
```

**IMPORTANTE:** 
- `SESSION_COOKIE_PATH=/sertecapp` - Esto permite que la cookie de sesión sea accesible en el subdirectorio
- `CSRF_COOKIE_PATH=/sertecapp` - Esto permite que la cookie CSRF sea accesible en el subdirectory
- Ambas DEBEN estar seteadas para que Livewire funcione

## Después de actualizar .env

1. SSH a Hostinger
2. `cd /home/u283281385/domains/demos.pendziuch.com/public_html/sertecapp/backend-laravel`
3. Ejecutar:
   ```bash
   php artisan config:cache
   php artisan cache:clear
   ```
4. Navegar a https://demos.pendziuch.com/sertecapp/admin/login
5. Intentar login nuevamente

## Si sigue fallando
- Check Laravel logs: `tail -100 backend-laravel/storage/logs/laravel.log`
- Usar browser DevTools → Network tab → hacer click en login, ver request a `/livewire/update`
- Verificar la respuesta: debería ser 200, no 419

## Alternativa: Usar SESSION_DRIVER=database
Si las cookies siguen fallando, cambiar a database driver:
```env
SESSION_DRIVER=database
SESSION_TABLE=sessions
```
Luego ejecutar: `php artisan migrate --force` para crear tabla de sesiones
