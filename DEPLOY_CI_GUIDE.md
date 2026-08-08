# Guía: Integración Continua en Hostinger con GitHub Webhooks

> Última actualización: 2026-07-31 — Solución definitiva con git archive ✅

## Qué logramos
Cada `deploy.bat "mensaje"` desde local actualiza Hostinger automáticamente en segundos. El deploy usa `git archive` para extraer el contenido exacto del remote — sin comparar fechas, sin rsync, sin parches manuales.

---

## El problema que tuvimos (y cómo se resolvió)

El repo tiene archivos en DOS paths distintos porque el repo en Hostinger tiene su root en `backend-laravel/` pero localmente el root es `SerTecApp/`:

| Path en GitHub | Quién lo usa |
|---|---|
| `app/Filament/...` | Hostinger (su repo root es `backend-laravel/`) |
| `backend-laravel/app/Filament/...` | Local (repo root es `SerTecApp/`) |

**Intentos fallidos:**
- `cp -rf` — pierde contra archivos con fecha más reciente en Hostinger
- `rsync --checksum` — compara checksums pero el `public_html/app/` que usa como fuente está FUERA del repo de Hostinger y nunca se actualiza con `git pull`

**La solución correcta: `git archive`**

`git archive` lee directamente del objeto git en el remote — ignora fechas, ignora archivos en disco, siempre extrae el contenido exacto de lo que está en GitHub. No hay forma de que falle por problemas de fechas o archivos locales más nuevos.

```bash
git fetch origin development
git archive origin/development backend-laravel/ | tar -xf - -C "$LARAVEL_DIR/" --strip-components=1
```

Lo que hace:
1. `git archive origin/development backend-laravel/` — crea un tar del contenido de `backend-laravel/` tal como está en GitHub
2. `--strip-components=1` — elimina el prefijo `backend-laravel/`
3. `-C "$LARAVEL_DIR/"` — extrae en `/public_html/backend-laravel/`
4. Resultado: archivos en el lugar correcto, con el contenido exacto de GitHub

---

## Arquitectura completa

```
Local (Windows)
    ↓ deploy.bat "mensaje"
    ↓ git add backend-laravel/ + commit + push
GitHub (repo, rama development)
    ↓ webhook POST a deploy.php
Hostinger deploy.php
    ↓ valida firma HMAC + lanza script en background
deploy-sertecapp.sh
    ↓ git fetch origin
    ↓ git archive origin/development backend-laravel/ | tar extract
    ↓ restaurar .env
    ↓ php artisan config:clear + cache:clear + view:clear
Hostinger (producción actualizada ✅ — siempre exactamente lo que está en GitHub)
```

---

## Script de deploy en Hostinger (versión final)

`/home/u283281385/deploy-sertecapp.sh`:

```bash
#!/bin/bash
LARAVEL_DIR="/home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel"
LOG="/tmp/sertecapp_deploy.log"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG"; }

log "=== DEPLOY INICIADO ==="

cd "$LARAVEL_DIR"

cp .env /tmp/sertecapp_env_backup
log ".env respaldado"

git fetch origin development >> "$LOG" 2>&1
log "git fetch completado"

git archive origin/development backend-laravel/ | tar -xf - -C "$LARAVEL_DIR/" --strip-components=1
log "git archive extraído correctamente"

cp /tmp/sertecapp_env_backup "$LARAVEL_DIR/.env"
log ".env restaurado"

/usr/bin/php artisan config:clear >> "$LOG" 2>&1
/usr/bin/php artisan cache:clear >> "$LOG" 2>&1
/usr/bin/php artisan view:clear >> "$LOG" 2>&1
chmod -R 775 storage bootstrap/cache 2>/dev/null

log "=== DEPLOY COMPLETADO ==="
```

Para crear/recrear en Hostinger:
```bash
cat > /home/u283281385/deploy-sertecapp.sh << 'EOF'
[contenido de arriba]
EOF
chmod +x /home/u283281385/deploy-sertecapp.sh
```

---

## deploy.bat local (versión simplificada)

```batch
@echo off
SET MSG=%~1
IF "%MSG%"=="" SET MSG=fix: actualizacion de codigo

cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp"
git add backend-laravel/app/ backend-laravel/config/ backend-laravel/routes/ backend-laravel/resources/ CLAUDE.md deploy.bat deploy-sertecapp.sh sertecapp-tecnicos/
git diff --cached --quiet && echo "Sin cambios" || git commit -m "%MSG%"
git push origin development
```

**Sin robocopy** — ya no hace falta porque `git archive` en Hostinger toma directamente de `backend-laravel/` en el remote.

---

## Webhook en GitHub

[github.com/PENDZIUCH/SerTecApp/settings/hooks](https://github.com/PENDZIUCH/SerTecApp/settings/hooks)

| Campo | Valor |
|---|---|
| Payload URL | `https://demo.pendziuch.com/deploy.php` |
| Content type | `application/json` |
| Secret | `SerTecDeploy2026!` |
| Events | Just the push event |
| Branch | `development` |

---

## Credenciales git en Hostinger

```bash
git remote set-url origin https://TOKEN@github.com/PENDZIUCH/SerTecApp.git
```

Token: sin expiración, scope `repo`. Nunca pegarlo en el chat.

---

## Uso diario

```cmd
deploy.bat "descripción del cambio"
```

El webhook dispara automáticamente. Sin SSH manual. Sin parches.

### Verificar deploy
```bash
tail -5 /tmp/sertecapp_deploy.log
```
Debe terminar con `=== DEPLOY COMPLETADO ===`.

---

## Para futuros proyectos en Hostinger — hacerlo bien desde el principio

Para evitar el problema de doble path, clonar el repo directamente en el nivel correcto:

```bash
cd /home/u283281385/domains/nuevo-dominio.com/
git clone https://TOKEN@github.com/USER/REPO.git backend-laravel
```

Así `backend-laravel/` es el repo clonado completo. `git pull` dentro de él actualiza `app/`, `config/`, etc. directamente sin necesidad de `git archive`.

El `deploy-sertecapp.sh` en ese caso se simplifica a:
```bash
cd /home/.../backend-laravel
cp .env /tmp/env_backup
git pull origin main
cp /tmp/env_backup .env
php artisan config:clear && php artisan cache:clear && php artisan view:clear
```

---

## Solución de problemas

### El webhook no dispara
GitHub → Settings → Webhooks → ver el último delivery. Si da 403: el secret no coincide.

### git archive falla
```bash
git fetch origin development
git archive origin/development backend-laravel/ | tar -tf - | head -20
```
Si lista archivos, el archive funciona. Si da error, verificar que el remote esté accesible.

### git pull pide contraseña
El token expiró. Generar uno nuevo y:
```bash
git remote set-url origin https://NUEVO_TOKEN@github.com/PENDZIUCH/SerTecApp.git
```

### Rollback de emergencia
```bash
git checkout v0.1-stable  # vuelve al estado estable
```
O desde local hacer `git push --force origin v0.1-stable:development` y re-deployar.



## Variables críticas del .env — se verifican en cada deploy

El script `deploy-sertecapp.sh` fuerza estas variables después de restaurar el `.env`:

```bash
# QUEUE_CONNECTION debe ser sync — no hay workers permanentes en Hostinger
sed -i 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' .env

# MAIL_MAILER debe ser smtp — sendmail está deshabilitado en Hostinger  
sed -i 's/MAIL_MAILER=sendmail/MAIL_MAILER=smtp/' .env
```

### Por qué QUEUE_CONNECTION=sync es crítico
- Hostinger hosting compartido no permite workers permanentes (`php artisan queue:work`)
- Con `QUEUE_CONNECTION=database`, las notificaciones (emails de recuperación de contraseña, notificaciones de Filament) se encolan pero nunca se procesan
- Con `QUEUE_CONNECTION=sync`, todo se ejecuta inmediatamente en el mismo request

### Síntoma si QUEUE_CONNECTION=database
- El email de prueba funciona (usa `Mail::raw()` directamente)
- El recupero de contraseña y notificaciones de Filament NO llegan
- El log muestra el email preparado pero nunca enviado

---

## Configuración de Email SMTP en Hostinger

### Cuenta de email
Crear en hPanel → Email → Crear cuenta: `mail@pendziuch.com`

### Configurar desde el admin
Super_admin → Administración → Configuración Email:

| Campo | Valor |
|---|---|
| Host SMTP | `smtp.hostinger.com` |
| Puerto | `465` |
| Cifrado | `SSL` |
| Usuario | `mail@pendziuch.com` |
| Contraseña | La que le pusiste en hPanel |
| Email remitente | `mail@pendziuch.com` |

Guardás → **recargás la página** → probás con Gmail como destino.

### ⚠️ Error crítico
**NUNCA usar Sendmail** — Hostinger lo deshabilita y deja `MAIL_MAILER=sendmail` en el `.env`, lo que rompe el SMTP.

Si el email deja de funcionar:
```bash
grep MAIL_MAILER /home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel/.env
```
Debe decir `MAIL_MAILER=smtp`. Si no:
```bash
cd /home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel && sed -i 's/MAIL_MAILER=sendmail/MAIL_MAILER=smtp/' .env && php artisan config:clear
```

### Lo que NO funciona en Hostinger
- Sendmail — deshabilitado
- Envío entre cuentas del mismo dominio — no confiable
- Siempre probar con cuenta externa (Gmail)

---

## Referencia rápida

| Dato | Valor |
|---|---|
| SSH | `ssh -i "C:\Users\Hugo Pendziuch\.ssh\hostinger_sertecapp" -p 65002 u283281385@147.79.103.125` |
| Laravel dir | `/home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel` |
| Script deploy | `/home/u283281385/deploy-sertecapp.sh` |
| Log deploy | `/tmp/sertecapp_deploy.log` |
| Admin panel | `https://demo.pendziuch.com/sertecapp/login` |
| Webhook secret | `SerTecDeploy2026!` |
| Tag estable | `v0.1-stable` |


---

## Incidente 2026-08-08 — .env corrupto por PWA_URL sin salto de linea

### Que paso
Al agregar PWA_URL al .env de Hostinger via SSH con `echo >> .env`, la linea
se pegó sin salto de linea al final del valor de APP_NAME que tenia comillas.
El .env quedó:
  APP_NAME="[Servicio Técnico] FitnessCompany.com"PWA_URL=https://...
En vez de:
  APP_NAME="[Servicio Técnico] FitnessCompany.com"
  PWA_URL=https://...

Laravel no pudo parsear el .env y devolvió 500 en toda la app.

### Causa raiz
`echo 'PWA_URL=...' >> .env` no garantiza salto de linea previo.
Si el archivo no termina en newline, la linea nueva se pega a la anterior.

### Solucion aplicada
Se corrigió el .env manualmente via SSH con sed.
Se actualizó deploy-sertecapp.sh para usar `printf '\nPWA_URL=...\n'`
y agregar un sed de corrección que detecta si PWA_URL quedó pegado.

### Regla para el futuro
NUNCA agregar variables al .env de produccion con `echo >>`.
SIEMPRE usar `printf '\nVAR=valor\n' >> .env` o editar el archivo directamente.

### Estado al cerrar el incidente
- development en produccion: dd63c35 (estado pre-arquitectura, verificado)
- core/v1 en GitHub: todo el trabajo de arquitectura del 2026-08-08
- El trabajo de core/v1 NO fue mergeado a development
- Pendiente: merge de core/v1 a development cuando se resuelva el problema
  de canAccess() que hace queries a la DB durante el boot de Filament

### Causa real del 500 (descubierta post-incidente)
El error 500 NO fue por el merge de codigo — fue por el .env corrupto.
El codigo de core/v1 puede ser correcto. Antes de descartar el merge,
hay que probarlo con el .env en buen estado.