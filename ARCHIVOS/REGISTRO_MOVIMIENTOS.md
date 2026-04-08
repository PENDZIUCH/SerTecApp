# Registro de Movimientos a ARCHIVOS

**Fecha:** 2026-04-08  
**Ejecutado por:** Claude Code (claude-sonnet-4-6)  
**Motivo:** Limpieza del directorio raíz — mover archivos deprecated/obsoletos sin eliminar nada.

---

## Qué se movió y desde dónde

### Carpetas completas
Origen → Destino

| Carpeta | Estado antes de mover |
|---|---|
| `SerTecApp/backend/` → `ARCHIVOS/backend/` | Primera versión PHP simple, reemplazada por backend-laravel |
| `SerTecApp/backend-v2/` → `ARCHIVOS/backend-v2/` | Versión intermedia, reemplazada por backend-laravel |
| `SerTecApp/frontend/` → `ARCHIVOS/frontend/` | Next.js inicial (nov 2025), reemplazado por sertecapp-tecnicos |

### Archivos individuales
Todos movidos desde raíz `SerTecApp/` → `ARCHIVOS/`

| Archivo | Por qué se archivó |
|---|---|
| `ESTADO_ACTUAL_30DIC2025.md` | Resumen de sesión de dic 2025, obsoleto |
| `RESUMEN_06ENE2026.md` | Resumen de sesión de ene 2026, obsoleto |
| `RESUMEN_SESION_2025-12-11.md` | Resumen de sesión, obsoleto |
| `SESION_2024-12-09_RESUMEN.md` | Resumen de sesión, obsoleto |
| `SESION_WORKER_S1.md` | Documento de sesión, obsoleto |
| `STATUS.md` | Estado de nov 2025, arquitectura vieja (Laravel+MySQL+Hostinger) |
| `backup_d1.bat` | Script de backup D1 viejo |
| `backup_d1.php` | Script de backup D1 viejo |
| `rebuild.ps1` | Script PowerShell de rebuild viejo |
| `START_SERTECAPP.ps1` | Script de arranque viejo |
| `backend.zip` | ZIP de versión antigua |
| `sertecapp-deploy-20260309.zip` | ZIP de deploy del 09/03/2026 |
| `temp_ordenes.txt` | Archivo temporal |
| `upload_ftp.py` | Script FTP viejo |

---

## Cómo restituir un archivo

Para devolver cualquier archivo a la raíz, simplemente moverlo de vuelta:

```bash
# Ejemplo: restituir backup_d1.bat
mv "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\ARCHIVOS\backup_d1.bat" \
   "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backup_d1.bat"

# Ejemplo: restituir carpeta frontend completa
mv "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\ARCHIVOS\frontend" \
   "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\frontend"
```

---

## Estado de la raíz después de la limpieza

```
SerTecApp/
├── ARCHIVOS/              ← todo lo deprecated (este directorio)
├── backend-laravel/       ← ACTIVO: admin panel Filament, corre local con tunnel Cloudflare
├── sertecapp-tecnicos/    ← ACTIVO: PWA para técnicos
├── sertecapp-worker/      ← ACTIVO: API Cloudflare Workers + D1
├── database/              ← schemas y migraciones
├── docs/                  ← documentación
├── backups/               ← backups
├── reportes/              ← reportes
└── *.md                   ← docs vigentes (README, QUICKSTART, DEPLOY_GUIDE, etc.)
```
