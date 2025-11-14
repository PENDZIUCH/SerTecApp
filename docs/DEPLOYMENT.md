# 🚀 SerTecApp - Deployment Guide

## Arquitectura de Deployment

```
Frontend (PWA) → Vercel (CDN Global)
       ↓ API REST
Backend (PHP) → Hostinger
       ↓ DB
MySQL → Hostinger
```

---

## 📦 Prerequisites

- Node.js 18+
- PHP 8.2+
- MySQL 8.0+
- Git
- Cuenta Vercel (gratis)
- Hosting Hostinger activo

---

## 🎨 Frontend Deployment (Vercel)

### Paso 1: Preparar el proyecto

```bash
cd frontend
npm install
npm run build
```

### Paso 2: Deploy a Vercel

**Opción A: Vercel CLI**
```bash
npm i -g vercel
vercel login
vercel --prod
```

**Opción B: GitHub Integration (Recomendado)**
1. Push código a GitHub
2. Ir a vercel.com
3. "New Project" → Importar repo
4. Framework Preset: Next.js
5. Environment Variables:
   ```
   NEXT_PUBLIC_API_URL=https://api.sertecapp.pendziuch.com
   ```
6. Deploy

### Paso 3: Configurar Dominio

En Vercel:
1. Settings → Domains
2. Agregar: `app.sertecapp.pendziuch.com`
3. Configurar DNS en tu proveedor:
   ```
   CNAME app.sertecapp.pendziuch.com → cname.vercel-dns.com
   ```

### Paso 4: PWA Configuration

Vercel auto-detecta y sirve:
- `/manifest.json`
- `/service-worker.js`
- Todos los assets con cache headers optimizados

---

## 🔧 Backend Deployment (Hostinger)

### Paso 1: Preparar Hostinger

1. **Crear Base de Datos MySQL**
   - Panel Hostinger → MySQL Databases
   - Crear BD: `sertecapp_prod`
   - Usuario: `sertecapp_user`
   - Guardar credenciales

2. **Importar Schema**
   ```bash
   # Via phpMyAdmin o MySQL command
   mysql -u sertecapp_user -p sertecapp_prod < database/schema.sql
   ```

### Paso 2: Configurar Laravel

1. **Subir archivos vía FTP/SFTP**
   - Host: tu-dominio.com
   - Puerto: 21 (FTP) o 22 (SFTP)
   - Subir carpeta `backend/` a `public_html/api/`

2. **Configurar .env**
   ```bash
   # Crear .env en servidor
   cp .env.example .env
   nano .env
   ```

   Configuración .env:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://api.sertecapp.pendziuch.com

   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=sertecapp_prod
   DB_USERNAME=sertecapp_user
   DB_PASSWORD=tu_password_seguro

   JWT_SECRET=genera_uno_aleatorio_aqui
   ```

3. **Generar App Key**
   ```bash
   php artisan key:generate
   ```

4. **Permisos**
   ```bash
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

### Paso 3: Configurar Subdomain

1. **En Hostinger Panel:**
   - Domains → Subdomains
   - Crear: `api.sertecapp.pendziuch.com`
   - Document Root: `/public_html/api/public`

2. **SSL Certificate**
   - Hostinger auto-genera Let's Encrypt
   - O subir certificado custom

### Paso 4: .htaccess (Laravel)

Asegurar que existe en `/public/.htaccess`:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

## 🔐 Security Checklist

### Frontend
- ✅ HTTPS habilitado
- ✅ Environment variables seguras
- ✅ No API keys en código
- ✅ CSP headers configurados
- ✅ CORS configurado correctamente

### Backend
- ✅ APP_DEBUG=false en producción
- ✅ Passwords fuertes en .env
- ✅ JWT_SECRET único y seguro
- ✅ Rate limiting activado
- ✅ SQL injection protección (PDO)
- ✅ XSS protección activa
- ✅ CSRF tokens en forms

### Database
- ✅ Usuario con permisos mínimos
- ✅ No acceso remoto directo
- ✅ Backups automáticos habilitados
- ✅ Password complejo

---

## 📊 Monitoring

### Frontend (Vercel)
- Dashboard Analytics built-in
- Error tracking con Sentry (opcional)

### Backend
- Logs en `/storage/logs/laravel.log`
- Monitoring con:
  ```bash
  tail -f /var/log/apache2/error.log
  tail -f storage/logs/laravel.log
  ```

---

## 🔄 CI/CD con GitHub Actions

Crear `.github/workflows/deploy.yml`:

```yaml
name: Deploy SerTecApp

on:
  push:
    branches: [main]

jobs:
  deploy-frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Deploy to Vercel
        run: vercel --prod --token=${{ secrets.VERCEL_TOKEN }}
        
  deploy-backend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Deploy to Hostinger via FTP
        uses: SamKirkland/FTP-Deploy-Action@4.3.0
        with:
          server: ${{ secrets.FTP_SERVER }}
          username: ${{ secrets.FTP_USERNAME }}
          password: ${{ secrets.FTP_PASSWORD }}
```

---

## 🆘 Troubleshooting

### Frontend no carga
1. Verificar build exitoso en Vercel
2. Check browser console por errores
3. Verificar CORS desde backend

### API returns 500
1. Check `storage/logs/laravel.log`
2. Verificar permisos de directorios
3. Revisar credenciales DB en .env

### PWA no instala
1. Verificar HTTPS activo
2. Check manifest.json válido
3. Service worker registrado

### Offline sync no funciona
1. Verificar IndexedDB en DevTools
2. Check service worker activo
3. Network tab para ver requests

---

## 📈 Post-Deployment

1. **Testing**
   ```bash
   # Test API
   curl https://api.sertecapp.pendziuch.com/health
   
   # Test frontend
   curl https://app.sertecapp.pendziuch.com
   ```

2. **Smoke Tests**
   - [ ] Login funciona
   - [ ] CRUD básico funciona
   - [ ] Offline mode funciona
   - [ ] Instalación PWA funciona

3. **Monitoring Setup**
   - Configurar alertas
   - Setup backups automáticos
   - Documentar accesos

---

**Deployment Time Estimate:** 2-3 horas  
**Next Steps:** Ver MAINTENANCE.md para tareas recurrentes
