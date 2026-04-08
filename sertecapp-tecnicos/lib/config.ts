// Config central — nunca hardcodear URLs en los componentes
// Cambiá la URL con: php switch_api.php [worker|laravel|local]
export const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8787';
