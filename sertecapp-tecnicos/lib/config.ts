// Config central — nunca hardcodear URLs en los componentes
// Cambiá la URL con: php switch_api.php [worker|laravel|local]
export const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8787';

// URL pública donde vive esta PWA (metadata, Open Graph, etc).
// El fallback es el dominio actual — si el dominio cambia, seteá
// NEXT_PUBLIC_APP_URL como variable de build en Cloudflare Pages
// en vez de tocar código.
export const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://sertecapp.pendziuch.com';

// Clave pública VAPID para suscribirse a Web Push (pushManager.subscribe).
// Es pública a propósito (por eso el prefijo NEXT_PUBLIC_, va igual en el
// bundle del cliente) - hardcodeada como fallback porque Cloudflare Pages
// no tiene forma de setear una variable de BUILD (no runtime/Functions)
// vía wrangler CLI, solo por dashboard (confirmado 2026-09-18, ver
// CLAUDE.md). Si el par de claves VAPID se rota alguna vez en el backend
// (backend-laravel/.env, VAPID_PUBLIC_KEY), actualizar este valor acá
// también - son el mismo par. Sigue pudiéndose overridear con
// NEXT_PUBLIC_VAPID_PUBLIC_KEY si algún día se carga bien por dashboard.
export const VAPID_PUBLIC_KEY = process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY
  || 'BK8KYAiB4p2ygpxe6APagoJ_EQhCPQKMVp4FN0lxeGWFj_EQ6D_gm_bvBiwSYhjTVn1twnG-NQe1mp0nMJXkhiQ';
