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

_(vacío por ahora)_

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
