# 🚀 Setup Local - SerTecApp

## Quick Start

### Backend (Laravel)

```bash
cd backend-laravel

# 1. Copiar configuración
cp .env.example .env.local

# 2. Editar .env.local con tus credenciales
# DB_HOST=127.0.0.1
# DB_DATABASE=sertecapp
# DB_USERNAME=root
# DB_PASSWORD=(tu password Laragon)

# 3. Instalar dependencias
composer install

# 4. Generar APP_KEY
php artisan key:generate

# 5. Migraciones
php artisan migrate

# 6. (Opcional) Seeders
php artisan db:seed

# 7. Levantar servidor
php artisan serve

# ✅ Acceder en http://localhost:8000
# ✅ Filament Admin en http://localhost:8000/admin
```

### Frontend (Next.js)

```bash
cd sertecapp-tecnicos

# 1. Copiar configuración
cp .env.example .env.local

# 2. Instalar dependencias
npm install

# 3. Levantar servidor
npm run dev

# ✅ Acceder en http://localhost:3000
```

---

## ⚠️ NO Commitear Estos Archivos

- `.env.local` - Usa `.env.example` en lugar
- `node_modules/`, `vendor/` - Git ignore
- `.next/`, `build/` - Build outputs
- Cualquier archivo con contraseña

---

## 🔐 Variables de Entorno

### Backend - `.env.local` (No commit)

Copia de `.env.example` + rellena valores locales

### Frontend - `.env.local` (No commit)

Copia de `.env.example`

---

## Verificar Setup

```bash
# Backend activo
curl http://localhost:8000/api/v1/login

# Frontend activo
curl http://localhost:3000
```

---

## Si Algo Falla

1. Revisa `.env.local` tiene credenciales correctas
2. BD está corriendo (MySQL/Laragon activo)
3. Puertos no están en uso (3000, 8000)
4. Dependencies instaladas (`npm install`, `composer install`)
5. Migraciones corridas (`php artisan migrate`)

---

Para más detalles ver `SECURITY_FLOW.md` y `project_structure.md`.
