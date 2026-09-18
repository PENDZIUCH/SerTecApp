# Pendientes — detalles a corregir

> Backlog vivo. Hugo va tirando ítems en el chat a medida que los encuentra
> usando la app real — se anotan acá tal cual, sin perder detalle, para no
> depender de la memoria de una conversación puntual (que no viaja entre
> sesiones). Se revisan y atacan en tandas, agrupados por tipo, no
> necesariamente uno por uno apenas aparecen.

## Cómo se usa

- **Nuevo ítem**: se agrega bajo "Sin triar" tal como lo contó Hugo (fecha +
  descripción + dónde/cómo se vio). No se prioriza ni se filtra al anotarlo.
- **Triado**: al revisar en tanda, se mueve a Bugs reales / Ajustes de
  UI-texto / Ideas para más adelante, con una línea de diagnóstico si ya se
  investigó.
- **Resuelto**: se tacha o se mueve a "Resueltos" con el commit que lo
  arregló — no se borra, queda de referencia.

---

## Sin triar

_(vacío por ahora)_

## Bugs reales

_(vacío por ahora)_

## Ajustes de UI / texto

- **2026-09-18 — `Gestión de Usuarios` (PWA) no distingue `supervisor` bien.**
  Encontrado en la auditoría de duplicación pendiente (`sertecapp-tecnicos/app/admin/gestion/page.tsx`):
  1. `esCuentaAdminTier()` (línea 51) no incluye `'supervisor'` en la lista de
     roles — según el comentario de arriba, un supervisor no debería poder
     editar la cuenta de *otro* supervisor, pero como esta función devuelve
     `false` para una cuenta supervisor, el botón de editar igual se muestra.
     El backend sí lo bloquea (403) según el propio comentario, así que no es
     un agujero de seguridad — solo un botón que aparece y después falla.
  2. El badge de rol (línea 152) usa `u.roles[0]` — muestra y colorea solo el
     primer rol del array, no todos. Si una cuenta tiene más de un rol (como
     pasaba con la de Hugo antes), puede mostrar el rol "equivocado" según el
     orden en que vinieron. `supervisor` tampoco tiene color propio, cae al
     estilo por defecto (azul, igual que técnico).
  No es nada roto para el uso normal de hoy — encontrado al auditar, no
  reportado por un usuario real.

## Ideas para más adelante

- **2026-09-17 — Auditar duplicación PWA-admin vs Filament.** Filament y
  la PWA tienen pantallas separadas (construidas en stacks distintos) para
  lo mismo: Clientes, Usuarios, Importar Excel, Detalle de Orden. Comparten
  la misma API/base de datos (no hay riesgo de datos desincronizados), pero
  la UI de cada uno se mantiene aparte y puede quedar desactualizada una
  respecto de la otra sin que nadie se entere — pasó hoy con tipos de
  cliente (Filament se actualizó solo con el sistema nuevo, la PWA tenía
  su propia lista hardcodeada aparte y hubo que arreglarla a mano). Vale
  la pena una revisión con calma de las otras pantallas duplicadas
  (Usuarios/roles, Importar, Detalle de Orden) buscando el mismo patrón,
  no urgente, no se encontró nada roto hoy.

## Resueltos

- **Modo oscuro en las 6 pantallas de admin de la PWA** (commit `14fa9af`,
  2026-09-17) — mismo mapeo de colores ya probado en técnico, 296 clases
  `dark:` agregadas. Sin tocar lógica, build verificado antes de subir.
