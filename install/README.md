# SertecCore — Guia de instalacion de nueva instancia

## Prerequisitos en Hostinger hPanel

### 1. Crear el subdominio o addon domain
- Ir a hPanel > Dominios > Subdominios
- Crear: `cliente.app.pendziuch.com`
- Directorio: `/home/u283281385/domains/cliente.app.pendziuch.com/public_html`

### 2. Crear la base de datos MySQL
- Ir a hPanel > Bases de datos > MySQL
- Crear DB: `u283281385_cliente`
- Crear usuario: `u283281385_cliente`
- Asignar usuario a la DB con todos los permisos

### 3. Conectarse por SSH

```bash
ssh -i ~/.ssh/hostinger_sertecapp -p 65002 u283281385@147.79.103.125
```

### 4. Correr el instalador

```bash
bash /ruta/al/install.sh \
  --client="Nombre del Cliente" \
  --domain="cliente.app.pendziuch.com" \
  --email="admin@cliente.com" \
  --db-name="u283281385_cliente" \
  --db-user="u283281385_cliente" \
  --db-pass="PASSWORD_DB" \
  --mail-user="mail@pendziuch.com" \
  --mail-pass="PASSWORD_MAIL" \
  --modules="customers,work_orders,equipment,parts"
```

## Modulos disponibles

| Modulo | Clave |
|--------|-------|
| Clientes | `customers` |
| Ordenes + Partes | `work_orders` |
| Visitas / Agenda | `visits` |
| Presupuestos | `budgets` |
| Stock / Repuestos | `parts` |
| Taller | `workshop` |
| Suscripciones | `subscriptions` |
| Equipamiento | `equipment` |

## Post-instalacion

1. Acceder a `https://cliente.app.pendziuch.com/sertecapp`
2. Login con el email y contrasena temporal del resumen
3. Cambiar contrasena en primer ingreso
4. Configurar email SMTP en Administracion > Configuracion Email
5. Crear usuarios tecnicos y enviar acceso por email

## Rollback

Si algo falla durante la instalacion:
```bash
rm -rf /home/u283281385/domains/cliente.app.pendziuch.com/public_html/*
```
Eliminar la DB desde hPanel y volver a correr el instalador.

## PWA para tecnicos (Next.js)

La PWA se deploya por separado en Cloudflare Pages.
Ver: `sertecapp-tecnicos/README.md`