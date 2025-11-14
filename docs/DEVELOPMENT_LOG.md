# 💰 SerTecApp - Development Cost Log

## 📊 Resumen Ejecutivo

**Proyecto:** SerTecApp - Sistema de Gestión de Servicio Técnico  
**Cliente:** Pendziuch International Devs  
**Región:** CABA/GBA, Argentina  
**Fecha Inicio:** Noviembre 13, 2025  
**Estado:** En Desarrollo

---

## 💵 Tarifas de Referencia (USD/hora)

### Mercado CABA/GBA - Noviembre 2025

| Nivel | USD/hora | ARS/hora (aprox @$1,150) |
|-------|----------|--------------------------|
| Junior Developer | $15-25 | $17,250-28,750 |
| Mid-Level Developer | $30-45 | $34,500-51,750 |
| Senior Developer | $50-70 | $57,500-80,500 |
| Tech Lead/Architect | $80-120 | $92,000-138,000 |
| Full Team (avg) | $40-60 | $46,000-69,000 |

**Nota:** Tarifas actualizables vía API dólar blue + ajuste por inflación

---

## 🏗️ Módulos Desarrollados y Costeo

### FASE 1: Arquitectura y Setup (COMPLETADO)

| Tarea | Horas | Nivel | USD | Descripción |
|-------|-------|-------|-----|-------------|
| Análisis de Requerimientos | 4h | Senior | $240 | Análisis del negocio, flujos, requisitos |
| Arquitectura del Sistema | 6h | Architect | $600 | Diseño de arquitectura, stack, deployment |
| Diseño de Base de Datos | 8h | Senior | $480 | Modelado completo, relaciones, índices, vistas |
| Estructura de Proyecto | 2h | Mid | $80 | Setup folders, configs iniciales |
| Documentación README | 3h | Mid | $120 | Documentación principal del proyecto |
| **Subtotal Fase 1** | **23h** | | **$1,520** | |

### FASE 2: Backend API (EN PROCESO)

| Tarea | Horas | Nivel | USD | Descripción |
|-------|-------|-------|-----|-------------|
| Setup Laravel + Composer | 2h | Mid | $80 | Instalación y configuración inicial |
| Sistema de Autenticación | 8h | Senior | $480 | JWT, roles, permisos, middleware |
| CRUD Clientes | 6h | Mid | $240 | API completa de clientes |
| CRUD Abonos | 6h | Mid | $240 | Gestión de abonos y frecuencias |
| CRUD Órdenes de Trabajo | 10h | Senior | $600 | Sistema completo de partes |
| CRUD Repuestos | 5h | Mid | $200 | Inventario de repuestos |
| Gestión Taller | 6h | Mid | $240 | Control de equipos en taller |
| Sistema de Facturación | 8h | Senior | $480 | Facturas + preparación Tango |
| Mock API Tango | 4h | Senior | $240 | Simulación de integración |
| API Documentación (Swagger) | 3h | Mid | $120 | Docs automática de endpoints |
| Testing Backend | 10h | Senior | $600 | Unit tests + integration tests |
| **Subtotal Fase 2** | **68h** | | **$3,520** | |

### FASE 3: Frontend PWA (EN PROCESO)

| Tarea | Horas | Nivel | USD | Descripción |
|-------|-------|-------|-----|-------------|
| Setup Next.js + TypeScript | 2h | Mid | $80 | Configuración inicial |
| Diseño UI/UX | 12h | Senior | $720 | Wireframes, mockups, design system |
| Sistema de Componentes UI | 10h | Senior | $600 | Librería de componentes reutilizables |
| Layout y Navegación | 6h | Mid | $240 | Header, sidebar, routing |
| Página Login/Auth | 6h | Mid | $240 | Autenticación frontend |
| Dashboard Principal | 8h | Senior | $480 | Vista general con KPIs |
| CRUD Clientes (Frontend) | 10h | Mid | $400 | Pantallas + formularios |
| CRUD Abonos (Frontend) | 8h | Mid | $320 | Gestión visual de abonos |
| CRUD Órdenes (Frontend) | 14h | Senior | $840 | Formularios complejos, firma digital |
| Gestión Repuestos (Frontend) | 8h | Mid | $320 | Stock, búsqueda, alertas |
| Vista Taller | 6h | Mid | $240 | Control de equipos |
| Sistema de Facturación (UI) | 8h | Senior | $480 | Generación y envío facturas |
| Planilla Control Abonos | 10h | Senior | $600 | Vista con colores configurables |
| Responsive Design | 8h | Mid | $320 | Mobile + tablet optimization |
| Dark/Light Mode | 4h | Mid | $160 | Temas intercambiables |
| **Subtotal Fase 3** | **120h** | | **$6,040** | |

### FASE 4: PWA Offline-First (CRÍTICO)

| Tarea | Horas | Nivel | USD | Descripción |
|-------|-------|-------|-----|-------------|
| Service Workers Setup | 8h | Senior | $480 | Configuración PWA básica |
| IndexedDB Implementation | 12h | Senior | $720 | Storage local completo |
| Sync Queue System | 10h | Architect | $1,000 | Cola de sincronización inteligente |
| Detección de Conexión | 4h | Mid | $160 | Online/offline detection |
| Background Sync | 8h | Senior | $480 | Sincronización automática |
| Conflict Resolution | 6h | Architect | $600 | Manejo de conflictos de datos |
| Cache Strategies | 6h | Senior | $360 | Cache inteligente de assets |
| PWA Manifest + Icons | 3h | Mid | $120 | Instalabilidad |
| Push Notifications | 6h | Senior | $360 | Notificaciones push |
| Testing Offline Scenarios | 8h | Senior | $480 | Tests exhaustivos offline |
| **Subtotal Fase 4** | **71h** | | **$4,760** | |

### FASE 5: Features Avanzados

| Tarea | Horas | Nivel | USD | Descripción |
|-------|-------|-------|-----|-------------|
| Sistema de Reportes | 12h | Senior | $720 | Reportes personalizados |
| Dashboard Analytics | 14h | Architect | $1,400 | Métricas, gráficos, insights |
| Exportación Excel/PDF | 6h | Mid | $240 | Generación de documentos |
| Búsqueda Avanzada | 8h | Senior | $480 | Filtros complejos, full-text |
| Sistema de Notificaciones | 6h | Mid | $240 | Notificaciones in-app |
| Auditoría de Cambios | 8h | Senior | $480 | Log de todas las acciones |
| Configuración Multi-Idioma | 10h | Senior | $600 | i18n completo ES/EN |
| Sistema de Permisos Granular | 8h | Architect | $800 | Control fino de accesos |
| **Subtotal Fase 5** | **72h** | | **$4,960** | |

### FASE 6: Integración y Deploy

| Tarea | Horas | Nivel | USD | Descripción |
|-------|-------|-------|-----|-------------|
| Integración Real Tango API | 12h | Architect | $1,200 | Conexión real con Tango |
| Setup Vercel (Frontend) | 2h | Mid | $80 | Deploy frontend |
| Setup Hostinger (Backend) | 4h | Senior | $240 | Deploy backend + DB |
| CI/CD Pipeline | 8h | Senior | $480 | GitHub Actions automation |
| SSL + Seguridad | 4h | Senior | $240 | HTTPS, headers, CORS |
| Monitoring + Logging | 6h | Senior | $360 | Sentry, logs, alertas |
| Backup Automático | 4h | Mid | $160 | Backups DB automatizados |
| Documentación Deploy | 4h | Mid | $160 | Guías de deployment |
| **Subtotal Fase 6** | **44h** | | **$2,920** | |

### FASE 7: Testing y QA

| Tarea | Horas | Nivel | USD | Descripción |
|-------|-------|-------|-----|-------------|
| Unit Testing Backend | 16h | Senior | $960 | Tests unitarios PHP |
| Unit Testing Frontend | 16h | Senior | $960 | Tests unitarios React |
| Integration Tests | 12h | Senior | $720 | Tests de integración |
| E2E Testing | 16h | Architect | $1,600 | Cypress/Playwright tests |
| Performance Testing | 8h | Senior | $480 | Load tests, optimization |
| Security Audit | 8h | Architect | $800 | Auditoría de seguridad |
| User Acceptance Testing | 12h | Mid | $480 | UAT con cliente real |
| Bug Fixing | 20h | Senior | $1,200 | Corrección de bugs |
| **Subtotal Fase 7** | **108h** | | **$7,200** | |

### FASE 8: Documentación Final

| Tarea | Horas | Nivel | USD | Descripción |
|-------|-------|-------|-----|-------------|
| Manual de Usuario | 12h | Mid | $480 | Guía completa de uso |
| API Documentation | 6h | Senior | $360 | Swagger/Postman collections |
| Code Documentation | 8h | Senior | $480 | JSDoc, PHPDoc, comentarios |
| Video Tutoriales | 8h | Mid | $320 | Screencasts de features |
| Troubleshooting Guide | 4h | Senior | $240 | Guía de problemas comunes |
| **Subtotal Fase 8** | **38h** | | **$1,880** | |

---

## 📊 TOTALES POR FASE

| Fase | Horas | USD | % del Total |
|------|-------|-----|-------------|
| 1. Arquitectura y Setup | 23h | $1,520 | 4.2% |
| 2. Backend API | 68h | $3,520 | 12.5% |
| 3. Frontend PWA | 120h | $6,040 | 17.5% |
| 4. PWA Offline-First | 71h | $4,760 | 13.0% |
| 5. Features Avanzados | 72h | $4,960 | 13.2% |
| 6. Integración y Deploy | 44h | $2,920 | 8.1% |
| 7. Testing y QA | 108h | $7,200 | 19.8% |
| 8. Documentación Final | 38h | $1,880 | 5.4% |
| **Gestión de Proyecto (15%)** | 84h | $4,200 | 6.3% |
| **TOTAL PROYECTO** | **628h** | **$37,000** | **100%** |

---

## 💡 Desglose por Tipo de Trabajo

| Categoría | Horas | USD | % |
|-----------|-------|-----|---|
| Backend Development | 110h | $6,200 | 16.8% |
| Frontend Development | 160h | $8,240 | 22.3% |
| PWA/Offline Implementation | 71h | $4,760 | 12.9% |
| Testing & QA | 108h | $7,200 | 19.5% |
| Architecture & Design | 45h | $3,720 | 10.1% |
| DevOps & Deploy | 44h | $2,920 | 7.9% |
| Documentation | 50h | $2,360 | 6.4% |
| Project Management | 40h | $2,600 | 7.0% |

---

## 🎯 Paquetes de Venta Sugeridos

### MVP (Minimum Viable Product)
**Incluye:** Fases 1, 2, 3 básico, Deploy simple  
**Horas:** ~200h  
**Precio:** $11,000 USD  
**Entrega:** 6-8 semanas

### Professional (Recomendado)
**Incluye:** Fases 1-6 completas + Testing básico  
**Horas:** ~400h  
**Precio:** $24,000 USD  
**Entrega:** 10-12 semanas

### Enterprise (Full Stack)
**Incluye:** TODO (Fases 1-8)  
**Horas:** 628h  
**Precio:** $37,000 USD  
**Entrega:** 14-16 semanas

---

## 🔄 Mantenimiento Mensual

| Servicio | Horas/mes | USD/mes | Descripción |
|----------|-----------|---------|-------------|
| Support Básico | 4h | $200 | Respuesta bugs críticos |
| Support Standard | 8h | $360 | + Mejoras menores |
| Support Premium | 16h | $800 | + Features nuevos |
| Hosting & Infraestructura | - | $50-100 | Vercel + Hostinger |

---

## 📈 ROI y Escalabilidad

### Potencial SaaS
Con adaptación multi-tenant (+ 120h / $7,200):
- **Precio por cliente:** $50-150/mes
- **Break-even:** 250-300 clientes
- **Potencial anual:** $180K-540K con 1,000 clientes

### Valor de Reventa
**Código base limpio y documentado:** $15,000-25,000 USD

---

## 🎓 Skills y Tecnologías Utilizadas

**Frontend:**
- Next.js 15, React 19, TypeScript
- Tailwind CSS, shadcn/ui
- Service Workers, IndexedDB
- PWA APIs

**Backend:**
- PHP 8.2, Laravel 11
- MySQL 8.0, Redis
- JWT Auth, REST API

**DevOps:**
- Git, GitHub Actions
- Vercel, Hostinger
- Docker (opcional)

**Testing:**
- PHPUnit, Jest, Cypress
- Postman, Swagger

---

**Última actualización:** Noviembre 13, 2025  
**Próxima revisión:** Cada milestone completado
