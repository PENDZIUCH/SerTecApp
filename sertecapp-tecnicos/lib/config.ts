// Config central — nunca hardcodear URLs en los componentes
// Cambiá la URL con: php switch_api.php [worker|laravel|local]
export const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8787';

// URL pública donde vive esta PWA (metadata, Open Graph, etc).
// El fallback es el dominio actual — si el dominio cambia, seteá
// NEXT_PUBLIC_APP_URL como variable de build en Cloudflare Pages
// en vez de tocar código.
export const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://sertecapp.pendziuch.com';
