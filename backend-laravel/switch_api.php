<?php
/**
 * switch_api.php v2
 * Cambia la URL del API en .env.local — UN SOLO LUGAR
 * El frontend usa NEXT_PUBLIC_API_URL de .env.local
 * Uso: php switch_api.php [worker|laravel|local]
 */

$target = $argv[1] ?? 'worker';

$urls = [
    'worker'  => 'https://sertecapp-worker.pendziuch.workers.dev',
    'laravel' => 'https://sertecapp.pendziuch.com',
    'local'   => 'http://localhost:8787',
];

if (!isset($urls[$target])) {
    echo "Uso: php switch_api.php [worker|laravel|local]\n";
    exit(1);
}

$newUrl = $urls[$target];
$envFile = __DIR__ . '/../sertecapp-tecnicos/.env.local';
$configFile = __DIR__ . '/../sertecapp-tecnicos/lib/config.ts';

// Actualizar .env.local
$env = file_get_contents($envFile);
$env = preg_replace('/NEXT_PUBLIC_API_URL=.*/', "NEXT_PUBLIC_API_URL=$newUrl", $env);
file_put_contents($envFile, $env);
echo "✅ .env.local → $newUrl\n";

// Actualizar config.ts
$config = "// Config central — nunca hardcodear URLs en los componentes\n";
$config .= "// Cambiá la URL con: php switch_api.php [worker|laravel|local]\n";
$config .= "export const API_URL = process.env.NEXT_PUBLIC_API_URL || '$newUrl';\n";
file_put_contents($configFile, $config);
echo "✅ lib/config.ts → $newUrl\n";

// Reemplazar URLs hardcodeadas en archivos del frontend (por si quedaron)
$oldUrls = array_values(array_filter($urls, fn($u) => $u !== $newUrl));
$frontendDir = __DIR__ . '/../sertecapp-tecnicos/app';
$changed = 0;

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($frontendDir));
foreach ($iterator as $file) {
    if (!in_array($file->getExtension(), ['tsx', 'ts', 'js'])) continue;
    $content = file_get_contents($file->getPathname());
    $newContent = str_replace($oldUrls, $newUrl, $content);
    if ($newContent !== $content) {
        file_put_contents($file->getPathname(), $newContent);
        echo "✅ " . str_replace($frontendDir . DIRECTORY_SEPARATOR, '', $file->getPathname()) . "\n";
        $changed++;
    }
}

foreach (['../sertecapp-tecnicos/lib', '../sertecapp-tecnicos/hooks'] as $extraDir) {
    $dir = __DIR__ . '/' . $extraDir;
    if (!is_dir($dir)) continue;
    foreach (glob($dir . '/*.{ts,tsx,js}', GLOB_BRACE) as $file) {
        if (basename($file) === 'config.ts') continue; // ya lo manejamos
        $content = file_get_contents($file);
        $newContent = str_replace($oldUrls, $newUrl, $content);
        if ($newContent !== $content) {
            file_put_contents($file, $newContent);
            echo "✅ " . basename($file) . "\n";
            $changed++;
        }
    }
}

echo "\n→ Todo apunta a: $newUrl\n";
echo "→ Para dev local del Worker: php switch_api.php local\n";
echo "→ Para producción Worker:    php switch_api.php worker\n";
echo "→ Para Laravel (fallback):   php switch_api.php laravel\n";
