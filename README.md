# 🔧 SerTecApp - Sistema de Gestión de Servicio Técnico

## 📋 Descripción

SerTecApp es un sistema completo de gestión para servicios técnicos de equipamiento deportivo (gimnasios). Permite gestionar clientes, abonos, órdenes de trabajo, repuestos, facturación y más, con capacidad de trabajo offline.

## 🎯 Características Principales

### ✨ Funcionalidades Core
- **Gestión de Clientes Abonados**: Control de clientes con contratos de mantenimiento mensual
- **Órdenes de Trabajo**: Sistema completo de partes de trabajo con seguimiento
- **Control de Abonos**: Planillas por frecuencia (1, 2, 3 visitas mensuales)
- **Gestión de Repuestos**: Inventario y uso en trabajos
- **Taller/Stock**: Control de equipos en reparación
- **Facturación**: Integración lista para API de Tango Software
- **Trabajo Offline**: PWA con sincronización automática cuando hay conexión

### 🎨 Diseño y UX
- Interfaz moderna y responsive
- Dark mode y light mode
- Optimizado para móviles y tablets (técnicos en campo)
- Instalable como app (PWA)

### 🔐 Seguridad
- Autenticación de usuarios
- Roles y permisos
- Backup automático de datos
- Cifrado de información sensible

## 🏗️ Arquitectura

### Stack Tecnológico

#### Frontend (PWA)
- **Next.js 15** - Framework React con App Router
- **TypeScript** - Tipado estático
- **Tailwind CSS** - Estilos utility-first
- **shadcn/ui** - Componentes UI modernos
- **IndexedDB** - Storage offline
- **Service Workers** - Capacidades PWA

#### Backend (API REST)
- **PHP 8.2+** - Lenguaje principal
- **Laravel 11** - Framework backend
- **MySQL 8.0** - Base de datos
- **Composer** - Gestor de dependencias

#### Deployment
- **Frontend**: Vercel (gratis, CDN global)
- **Backend**: Hostinger (plan actual)
- **Database**: MySQL en Hostinger

## 📁 Estructura del Proyecto

```
SerTecApp/
├── frontend/              # Aplicación Next.js PWA
│   ├── app/              # App Router (páginas)
│   ├── components/       # Componentes React
│   │   ├── layout/      # Layout y navegación
│   │   ├── ui/          # Componentes UI reutilizables
│   │   ├── forms/       # Formularios
│   │   └── tables/      # Tablas de datos
│   ├── lib/             # Utilidades y configuración
│   ├── services/        # API calls y servicios
│   ├── types/           # TypeScript types
│   └── hooks/           # React hooks personalizados
│
├── backend/              # API PHP Laravel
│   ├── api/             # Endpoints REST
│   ├── config/          # Configuraciones
│   ├── models/          # Modelos de datos
│   ├── controllers/     # Controladores
│   ├── middleware/      # Middleware (auth, cors, etc)
│   └── utils/           # Utilidades PHP
│
├── database/            # Scripts SQL y migraciones
│   ├── migrations/      # Migraciones de BD
│   ├── seeders/         # Datos de prueba
│   └── schema.sql       # Esquema completo
│
└── docs/               # Documentación completa
    ├── API.md          # Documentación de endpoints
    ├── DATABASE.md     # Esquema de base de datos
    ├── DEPLOYMENT.md   # Guía de deployment
    └── DEVELOPMENT.md  # Guía para desarrolladores
```

## 🚀 Instalación y Desarrollo

### Requisitos Previos
- Node.js 18+ y npm
- PHP 8.2+ 
- MySQL 8.0+
- Composer
- Laragon (recomendado para Windows) o MAMP (Mac)

### Instalación Frontend

```bash
cd frontend
npm install
npm run dev
```

La app estará en `http://localhost:3000`

### Instalación Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

La API estará en `http://localhost:8000`

## 📊 Modelo de Datos Principal

### Entidades Core

**Clientes**
- Datos básicos (nombre, contacto, dirección)
- Tipo: abonado o esporádico
- Frecuencia de visitas (1, 2, 3 mensuales)
- Estado: activo, inactivo, moroso

**Órdenes de Trabajo**
- Número de parte
- Cliente
- Fecha
- Técnico asignado
- Equipo atendido
- Descripción del trabajo
- Repuestos utilizados
- Estado: pendiente, en progreso, completado
- Firma del cliente (digital)

**Abonos**
- Cliente
- Frecuencia (visitas mensuales)
- Monto
- Fecha de inicio
- Estado

**Repuestos**
- Código
- Descripción
- Stock actual
- Precio
- Proveedor

**Equipos en Taller**
- Cliente/Origen
- Equipo
- Estado
- Fecha ingreso
- Observaciones

## 🔌 Integración Tango Software

El sistema está preparado para integrar con la API de Tango para facturación:

```typescript
// Endpoint simulado (mock) incluido
POST /api/tango/factura
{
  "cliente_id": 123,
  "items": [...],
  "total": 50000
}
```

Una vez aprobada la integración real, solo se cambia la URL del endpoint.

## 🎨 Sistema de Colores (Abonos)

- **Verde**: 1 visita mensual
- **Azul**: 2 visitas mensuales  
- **Morado**: 3 visitas mensuales

## 📱 Capacidades PWA

- ✅ Instalable en dispositivos
- ✅ Funciona offline
- ✅ Sincronización automática en background
- ✅ Notificaciones push
- ✅ Caché inteligente
- ✅ Actualización automática

## 🔄 Flujo de Trabajo Típico

1. **Técnico sin conexión** → Crea orden de trabajo offline
2. **Datos guardados** → IndexedDB local en el dispositivo
3. **Conexión restaurada** → Sincronización automática
4. **Backend actualizado** → Datos persistidos en MySQL
5. **Facturación** → Envío a Tango (cuando esté integrado)

## 📈 Roadmap y Fases

### Fase 1 - MVP (Actual)
- ✅ Estructura del proyecto
- 🚧 Frontend básico
- 🚧 Backend API
- 🚧 Offline storage
- 🚧 CRUD completo

### Fase 2 - SaaS
- Multi-tenant (varios clientes)
- Panel de administración
- Facturación automática
- Reportes avanzados
- Integración Tango real

### Fase 3 - Expansión
- App móvil nativa (React Native)
- Dashboard analytics
- Módulo de inventario avanzado
- Sistema de tickets
- WhatsApp integration

## 👥 Equipo y Colaboración

**Proyecto desarrollado por:**
- Hugo Pendziuch (Fundador/Developer)
- Claude AI (Arquitectura y Desarrollo)

**Para colaboradores:**
Todo el código está documentado y sigue estándares profesionales. Lee `/docs/DEVELOPMENT.md` para contribuir.

## 📄 Licencia

Propietario: Pendziuch.com
Todos los derechos reservados.

## 🤝 Soporte

Para preguntas o soporte:
- Web: pendziuch.com

---

**Versión**: 1.0.0-alpha  
**Última actualización**: Noviembre 2025  
**Estado**: En desarrollo activo
