// Config central — nunca hardcodear URLs en los componentes
// Cambiá la URL con: php switch_api.php [worker|laravel|local]
export const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8787';

// URL pública donde vive esta PWA (metadata, Open Graph, etc).
// El fallback es el dominio actual — si el dominio cambia, seteá
// NEXT_PUBLIC_APP_URL como variable de build en Cloudflare Pages
// en vez de tocar código.
export const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://sertecapp.pendziuch.com';

// Clave pública VAPID para suscribirse a Web Push (pushManager.subscribe).
// Sin valor por defecto a propósito: sin esta variable de build seteada,
// el hook usePushNotifications simplemente reporta "no soportado" en vez
// de fallar con una clave inválida. Setear NEXT_PUBLIC_VAPID_PUBLIC_KEY en
// Cloudflare Pages con el mismo valor de VAPID_PUBLIC_KEY del backend
// (backend-laravel/.env) - son el mismo par de claves.
export const VAPID_PUBLIC_KEY = process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY || '';
