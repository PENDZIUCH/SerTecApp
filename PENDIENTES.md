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

- **2026-09-17 — Modo oscuro no llega a las pantallas de admin de la PWA.**
  Las pantallas de técnico (Mis Órdenes, el parte, etc.) sí lo tienen — 44
  clases `dark:` solo en la de órdenes. Las 6 pantallas de admin
  (`app/admin/page.tsx`, `clientes`, `gestion`, `importar`, `orden`,
  `orden/[id]/_client.tsx`) tienen **cero**, ninguna. No es un switch
  manual en ningún lado — sigue solo la config de modo oscuro del
  dispositivo/navegador (`prefers-color-scheme`), automático.
  **Complejidad: simple y seguro, no chico.** Es puramente agregar la
  variante `dark:` de Tailwind al lado de cada clase de color que ya
  existe (ej. `bg-white` → `bg-white dark:bg-gray-800`) — no toca lógica,
  no puede romper nada funcional, mismo patrón ya probado y funcionando
  en las pantallas de técnico. Lo que sí es real: volumen — se contaron
  ~241 clases de color entre las 6 pantallas (76 en el panel principal,
  49 en clientes, 49 en el detalle de orden, 32-34 en gestión/importar).
  Es una pasada dedicada por cada pantalla, no un cambio de 5 minutos.

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

_(vacío por ahora)_
