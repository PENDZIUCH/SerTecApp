<?php
// switch_api.php — cambia la URL del API en todos los archivos del frontend
// Uso: php switch_api.php [worker|laravel]

$target = $argv[1] ?? 'worker';

$urls = [
    'worker'  => 'https://sertecapp-worker.pendziuch.workers.dev',
    'laravel' => 'https://sertecapp.pendziuch.com',
];

if (!isset($urls[$target])) {
    echo "Uso: php switch_api.php [worker|laravel]\n";
    exit(1);
}

$newUrl = $urls[$target];
$oldUrl = $target === 'worker' ? $urls['laravel'] : $urls['worker'];

$frontendDir = __DIR__ . '/../sertecapp-tecnicos/app';

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($frontendDir));
$changed = 0;

foreach ($files as $file) {
    if (!in_array($file->getExtension(), ['tsx', 'ts', 'js'])) continue;
    $content = file_get_contents($file->getPathname());
    if (strpos($content, $oldUrl) !== false) {
        $new = str_replace($oldUrl, $newUrl, $content);
        file_put_contents($file->getPathname(), $new);
        echo "✅ " . str_replace($frontendDir . DIRECTORY_SEPARATOR, '', $file->getPathname()) . "\n";
        $changed++;
    }
}

// También cambiar lib/ y hooks/
foreach (['../sertecapp-tecnicos/lib', '../sertecapp-tecnicos/hooks'] as $extraDir) {
    $dir = __DIR__ . '/' . $extraDir;
    if (!is_dir($dir)) continue;
    foreach (glob($dir . '/*.{ts,tsx,js}', GLOB_BRACE) as $file) {
        $content = file_get_contents($file);
        if (strpos($content, $oldUrl) !== false) {
            file_put_contents($file, str_replace($oldUrl, $newUrl, $content));
            echo "✅ " . basename($file) . "\n";
            $changed++;
        }
    }
}

echo "\n→ $changed archivos actualizados a: $newUrl\n";
