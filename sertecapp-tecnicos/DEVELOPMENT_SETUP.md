# Levantar sertecapp-tecnicos en Local (rama development)

## Estado actual (2026-04-09 16:00)
✅ App levantada en http://localhost:3000
✅ Configurada para conectar a backend Laravel en localhost:8000

## Cambio clave realizado

**Archivo**: `.env.local`

```env
NEXT_PUBLIC_API_URL=http://localhost:8000
NEXT_PUBLIC_ENV=development
```

**Por qué**: 
- Antes apuntaba a http://127.0.0.1:8788 (tunnel deprecado)
- Ahora apunta a localhost:8000 donde corre Laravel + Filament con MySQL (Laragon)

## Cómo iniciar

```bash
cd sertecapp-tecnicos
npm run dev
```

Acceder a: http://localhost:3000

## Requisitos previos
- Node.js instalado
- Laravel admin corriendo en localhost:8000 (con Laragon)
- MySQL levantado

## Arquitectura local
```
localhost:3000  ← Next.js app (técnico/admin)
       ↓ API calls
localhost:8000  ← Laravel + Filament admin
       ↓ queries
MySQL (Laragon) ← Base de datos
```

## Diferencias con Cloudflare
- **Local**: Next.js + Laravel + MySQL
- **Cloudflare**: Next.js Workers + D1 (SQLite)
- Las credenciales de DB y API URL cambian según el entorno
