# 🚀 SerTecApp - Guía Completa para Developers

> **IMPORTANTE**: Este documento es para CUALQUIER developer (humano o AI) que trabaje en el proyecto.
> Lee esto COMPLETO antes de hacer cualquier cambio.

---

## 📋 CONTEXTO DEL PROYECTO

### ¿Qué es SerTecApp?
Sistema de gestión de servicio técnico para empresas que dan mantenimiento a equipamiento (gimnasios, etc).

### Cliente Principal
**Fitness Company Argentina** (fitnesscompany.com.ar)
- Pagan: USD $300/mes x 12 meses = $3,600
- Necesitan: Sistema para gestionar órdenes de trabajo, clientes, técnicos
- Timeline: 2 meses desarrollo + 10 meses soporte

### Objetivo de Negocio
- **Corto plazo**: Entregar a Fitness Company
- **Largo plazo**: Vender como SaaS multi-tenant a otras empresas
- **Precio objetivo**: $200-500/mes por cliente

---

## 🏗️ ARQUITECTURA ACTUAL

### Stack Tecnológico

**Frontend (PWA)**
- Next.js 16 + React 19
- TypeScript
- Tailwind CSS v4
- Instalable como app (PWA)
- Funciona offline

**Backend (API REST)**
- PHP 8.2+ vanilla (NO Laravel todavía)
- MySQL 8.0
- Endpoints en `/backend/api/`
- Autenticación JWT

**Deployment**
- Local: Laragon (Windows) - `C:\laragon\www\SerTecApp`
- Frontend production: TBD (Vercel recomendado)
- Backend production: TBD (Hostinger o similar)

### Estructura de Directorios

```
SerTecApp/
├── frontend/
│   ├── app/
│   │   ├── page.tsx              ← APP PRINCIPAL (todo en 1 archivo por ahora)
│   │   ├── components/           ← Componentes React
│   │   │   ├── ClienteForm.tsx
│   │   │   ├── OrdenForm.tsx
│   │   │   ├── OrdenDetalle.tsx
│   │   │   └── Toast.tsx
│   │   ├── hooks/
│   │   │   └── useDarkMode.ts    ← Hook para dark mode
│   │   └── globals.css           ← Estilos + dark mode config
│   ├── public/
│   │   └── manifest.json         ← PWA config
│   └── package.json
│
├── backend/
│   ├── api/
│   │   └── index.php             ← Router principal
│   ├── config/
│   │   └── database.php          ← Conexión MySQL
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── ClientesController.php
│   │   ├── OrdenesController.php
│   │   ├── ConfiguracionController.php
│   │   └── RepuestosController.php
│   └── .env                      ← Credenciales (NO en Git)
│
├── database/
│   ├── schema.sql                ← Esquema completo de BD
│   └── configuracion.sql         ← Datos iniciales
│
├── docs/                         ← Documentación detallada
├── .gitignore
└── README.md
```

---

## 🔑 FEATURES IMPLEMENTADAS

### ✅ Funcionalidad Actual

**Autenticación**
- Login con email/password
- JWT token guardado en localStorage
- Usuario: `admin@sertecapp.com` / `admin123`

**Gestión de Clientes**
- CRUD completo
- Tipos: abonado / esporádico
- Colores por frecuencia: Verde (1 visita), Amarillo (2 visitas), Rojo (3 visitas)

**Órdenes de Trabajo**
- CRUD completo
- Estados: Pendiente, En Proceso, Completado
- Detalles de equipo, técnico, trabajo realizado
- Impresión de orden

**UI/UX**
- Dark mode funcional (localStorage persistente)
- Responsive mobile-first
- Menú mobile hamburguesa
- Menú desktop con navegación

**PWA**
- Manifest.json configurado
- Service Worker (TBD)
- Instalable como app

---

## 🚧 TRABAJO EN PROGRESO

### Feature Actual: AdminLTE Layout
**Branch**: `feature/adminlte-layout`  
**Responsable**: Dev principal  
**Estado**: Iniciando  

**Objetivo**: Reemplazar layout actual por diseño AdminLTE
- Sidebar colapsable izquierdo
- Header top profesional
- Cards de estadísticas
- Mantener TODA la funcionalidad actual

**Referencia**: https://adminlte.io/themes/v3/index2.html

---

## 🌳 GIT WORKFLOW

### Branches Principales

```
main                          ← Producción (solo código testeado)
├── develop                   ← Integración (merge de features)
├── feature/adminlte-layout   ← Layout AdminLTE (ACTIVO)
├── feature/multi-theme       ← Sistema de themes (PRÓXIMO)
└── feature/i18n              ← Multi-idioma (FUTURO)
```

### Workflow Estándar

**1. Crear nueva feature**
```bash
git checkout develop
git pull origin develop
git checkout -b feature/nombre-descriptivo
```

**2. Trabajar en tu branch**
```bash
# Hacer cambios
git add .
git commit -m "tipo: descripción breve"
git push origin feature/nombre-descriptivo
```

**3. Merge a develop (cuando esté testeado)**
```bash
git checkout develop
git merge feature/nombre-descriptivo
git push origin develop
```

**4. Deploy a producción (solo cuando esté 100% listo)**
```bash
git checkout main
git merge develop
git push origin main
```

### Convenciones de Commits

```
feat: nueva funcionalidad
fix: corrección de bug
docs: cambios en documentación
style: formato, punto y coma, etc (no afecta código)
refactor: refactorización de código
test: agregar tests
chore: cambios en build, CI, etc
```

**Ejemplos:**
```
feat: agregar sidebar colapsable AdminLTE
fix: corregir dark mode en formularios
docs: actualizar README con nuevas features
refactor: separar layout en componentes
```

---

## 👥 TRABAJO EN EQUIPO (MÚLTIPLES DEVS/AIs)

### Setup para Nuevo Developer

**Opción A: Misma máquina, diferente proyecto**
```bash
# Clonar en otro directorio
cd ~/projects
git clone https://github.com/PENDZIUCH/SerTecApp.git SerTecApp-dev2
cd SerTecApp-dev2
git checkout -b feature/mi-feature
```

**Opción B: Otra máquina (ej: Mac)**
```bash
git clone https://github.com/PENDZIUCH/SerTecApp.git
cd SerTecApp
npm install
# Crear tu branch
git checkout -b feature/mi-feature
```

### Evitar Conflictos

**REGLA DE ORO**: Un developer = Un área del código

**División sugerida:**
- **Dev 1**: Frontend/UI (componentes, estilos)
- **Dev 2**: Backend/API (controllers, endpoints)
- **Dev 3**: Documentación/Testing

**Si necesitas tocar el mismo archivo:**
1. Avisá en el equipo
2. Hacé pull frecuente
3. Commits pequeños y frecuentes
4. Revisá diff antes de commit

---

## 🎨 SISTEMA DE DISEÑO

### Colores Principales

```css
/* Light Mode */
--primary: #3B82F6 (Azul)
--success: #10B981 (Verde)
--warning: #F59E0B (Amarillo/Naranja)
--danger: #EF4444 (Rojo)

/* Dark Mode */
--bg-dark: #111827
--text-dark: #F9FAFB
```

### Colores de Clientes (por frecuencia)

- **1 visita/mes**: Verde (#10B981)
- **2 visitas/mes**: Amarillo (#EAB308)
- **3 visitas/mes**: Rojo (#EF4444)

### Tipografía

- Font: System fonts (Arial, Helvetica, sans-serif)
- Tamaños: Tailwind defaults (text-sm, text-base, text-lg, etc)

---

## 🔐 SEGURIDAD Y CREDENCIALES

### Variables de Entorno

**Frontend** (`.env.local` - NO en Git)
```env
NEXT_PUBLIC_API_URL=http://localhost/SerTecApp/backend
```

**Backend** (`.env` - NO en Git)
```env
DB_HOST=localhost
DB_NAME=sertecapp
DB_USER=root
DB_PASS=
JWT_SECRET=tu_secret_key_aqui
```

### Archivos que NUNCA deben ir a Git

```
.env
.env.local
node_modules/
.next/
update_password.php
reset_*.php
```

Ya están en `.gitignore` pero **siempre verificá** antes de commit.

---

## 🧪 TESTING

### Testing Manual (por ahora)

**Checklist antes de merge a develop:**

Frontend:
- [ ] Login funciona
- [ ] CRUD Clientes funciona
- [ ] CRUD Órdenes funciona
- [ ] Dark mode funciona
- [ ] Responsive mobile funciona
- [ ] Sin errores en consola

Backend:
- [ ] Todos los endpoints responden
- [ ] Autenticación JWT funciona
- [ ] Queries SQL no dan error
- [ ] CORS configurado correcto

### Testing en Diferentes Browsers

- Chrome/Edge (principales)
- Firefox
- Safari (iOS)
- Chrome Mobile (Android)

---

## 📱 PWA - Trabajo Offline

### Estado Actual
- `manifest.json` configurado ✅
- Service Worker pendiente ⏳
- IndexedDB pendiente ⏳

### Implementación Futura

**Service Worker** (`public/service-worker.js`):
```javascript
// Cache de assets estáticos
// Sincronización en background
// Notificaciones push
```

**IndexedDB**:
```javascript
// Guardar órdenes offline
// Sincronizar cuando hay conexión
```

---

## 🌍 INTERNACIONALIZACIÓN (i18n) - FUTURO

### Plan de Implementación

**Idiomas objetivo:**
1. Español (default)
2. Inglés
3. Alemán
4. Francés

**Librería**: `next-intl`

**Estructura:**
```
frontend/
└── locales/
    ├── es.json
    ├── en.json
    ├── de.json
    └── fr.json
```

**Uso:**
```tsx
import { useTranslations } from 'next-intl';

const t = useTranslations();
<h1>{t('dashboard.title')}</h1>
```

---

## 🎨 SISTEMA DE THEMES - FUTURO

### Plan de Arquitectura

```
frontend/
└── themes/
    ├── adminlte/       ← Theme actual
    ├── creative-tim/   ← Próximo
    └── material/       ← Futuro
```

**Cambio de theme:**
```typescript
// config/theme.ts
export const ACTIVE_THEME = 'adminlte';
```

**Separación:**
- **Layout**: Theme-specific
- **Components**: Theme-agnostic (reutilizables)
- **Business Logic**: Totalmente independiente del theme

---

## 🐛 DEBUGGING

### Logs y Errores

**Frontend:**
```typescript
// Usar console.log con prefijo
console.log('🔵 [Auth]:', data);
console.error('🔴 [API Error]:', error);
```

**Backend:**
```php
// Usar error_log
error_log("🔵 [Auth] Usuario logueado: " . $userId);
error_log("🔴 [Database] Error: " . $e->getMessage());
```

### Problemas Comunes

**"Internal Server Error" en frontend:**
- Verificar que Laragon esté corriendo
- Verificar CORS en backend
- Verificar que la BD existe
- Check logs en `backend/logs/`

**Dark mode no funciona:**
- Verificar localStorage en DevTools
- Verificar que `globals.css` tiene config correcta
- Verificar Tailwind config

**Git conflicts:**
```bash
git status
git diff
# Resolver manualmente
git add .
git commit -m "fix: resolver conflictos"
```

---

## 📞 COMUNICACIÓN DEL EQUIPO

### Para Developers AI (Claude, etc)

**Al iniciar sesión:**
1. Leer este archivo completo
2. Hacer `git status` para ver branch actual
3. Hacer `git pull` para traer últimos cambios
4. Preguntar al usuario qué feature trabajar

**Al terminar sesión:**
1. Commit de cambios
2. Push a tu branch
3. Documentar en este archivo si es necesario
4. Avisar al usuario qué quedó pendiente

### Para Hugo (owner)

**Antes de reunión con cliente:**
- Merge develop → main
- Deploy a producción
- Testing completo
- Preparar demo

**Reportes de avance:**
- Actualizar STATUS.md semanalmente
- Screenshots de avances en `/docs/screenshots/`
- Lista de bugs conocidos

---

## 🚀 DEPLOYMENT

### Desarrollo Local

**Frontend:**
```bash
cd frontend
npm run dev
# http://localhost:3000 o :3001 o :3002
```

**Backend:**
```bash
# Laragon ya sirve automáticamente
# http://localhost/SerTecApp/backend/api
```

### Producción (cuando esté listo)

**Frontend → Vercel:**
```bash
vercel --prod
```

**Backend → Hostinger:**
```bash
# Upload via FTP/SFTP
# Configurar .env en servidor
# Importar schema.sql
```

---

## 📊 ROADMAP Y PRIORIDADES

### Fase 1: MVP para Fitness Company (2 meses)
**Prioridad ALTA:**
- [x] Login y autenticación
- [x] CRUD Clientes
- [x] CRUD Órdenes
- [x] Dark mode
- [ ] Layout AdminLTE ← **AHORA**
- [ ] Impresión mejorada
- [ ] Reportes básicos
- [ ] Deploy producción
- [ ] Capacitación cliente

### Fase 2: SaaS Multi-tenant (3-6 meses)
- [ ] Sistema de themes
- [ ] Multi-idioma (i18n)
- [ ] Multi-tenant (varios clientes)
- [ ] Panel admin
- [ ] Billing/Subscriptions
- [ ] Integración Tango real

### Fase 3: Expansión (6-12 meses)
- [ ] App móvil nativa
- [ ] WhatsApp integration
- [ ] Analytics avanzado
- [ ] Marketplace de themes
- [ ] API pública para integraciones

---

## 💡 DECISIONES DE ARQUITECTURA

### ¿Por qué Next.js?
- SSR + CSR (mejor SEO)
- App Router moderno
- PWA capabilities
- Deployment fácil (Vercel)
- Gran comunidad

### ¿Por qué PHP vanilla y no Laravel?
- Cliente ya tiene hosting PHP
- Más simple para mantener
- Menos overhead
- Posible migración a Laravel en Fase 2

### ¿Por qué Tailwind CSS?
- Utility-first (rápido desarrollo)
- Dark mode built-in
- Responsive fácil
- File size pequeño en producción

### ¿Por qué PWA?
- Técnicos trabajan sin internet
- Instalable como app
- Mejor UX en mobile
- Sincronización automática

---

## 📚 RECURSOS Y REFERENCIAS

### Documentación Oficial
- Next.js: https://nextjs.org/docs
- React: https://react.dev
- Tailwind: https://tailwindcss.com
- AdminLTE: https://adminlte.io/docs

### Inspiración de Diseño
- AdminLTE Demo: https://adminlte.io/themes/v3/index2.html
- Creative Tim: https://www.creative-tim.com/templates/free

### APIs y Herramientas
- JWT: https://jwt.io
- PWA Builder: https://www.pwabuilder.com

---

## ⚠️ WARNINGS Y CUIDADOS

### NO HACER NUNCA:

❌ Pushear a `main` sin testing
❌ Commitear archivos con credenciales
❌ Borrar código sin hacer backup
❌ Hacer refactor masivo sin avisar
❌ Cambiar estructura de BD sin migración
❌ Romper funcionalidad existente sin avisar

### SIEMPRE HACER:

✅ Pull antes de empezar a trabajar
✅ Commits pequeños y frecuentes
✅ Messages de commit descriptivos
✅ Testing manual antes de merge
✅ Documentar decisiones importantes
✅ Avisar si algo está bloqueado

---

## 🎯 CHECKLIST PARA NUEVO DEV

Antes de empezar a codear:

- [ ] Leí este documento completo
- [ ] Cloné el repositorio
- [ ] Instalé dependencias (`npm install`)
- [ ] Configuré .env con credenciales
- [ ] Probé que corra local (frontend + backend)
- [ ] Entiendo el flujo de Git
- [ ] Sé en qué branch trabajar
- [ ] Sé qué feature me toca

---

## 📝 NOTAS FINALES

### Para Developers AI (Claude, ChatGPT, etc)

Este proyecto está siendo desarrollado por múltiples instancias de AI trabajando en paralelo. Es CRÍTICO:

1. **Leer este archivo SIEMPRE** antes de empezar
2. **No asumir nada** - todo está documentado aquí
3. **Preguntar antes de cambios grandes**
4. **Documentar decisiones nuevas** en este archivo
5. **Ser conservador** - mejor preguntar que romper

### Para Humanos

Si sos un developer humano:
- Bienvenido! Este proyecto fue construido por AI pero es 100% código normal
- No hay "magia" - es Next.js + PHP estándar
- Seguí los mismos workflows que cualquier proyecto
- La arquitectura está pensada para escalar

---

## 📧 CONTACTO

**Owner**: Hugo Pendziuch  
**Email**: [pendiente]  
**GitHub**: https://github.com/PENDZIUCH/SerTecApp  
**Cliente**: Fitness Company Argentina

---

**Última actualización**: Noviembre 17, 2025  
**Versión**: 1.0.0-alpha  
**Branch activo**: feature/adminlte-layout

---

> **💡 TIP FINAL**: Cuando tengas dudas, buscá en este archivo con Ctrl+F. Todo lo importante está acá.
> Si algo no está documentado, agregalo después de resolver el problema.

**¡Buen código! 🚀**
