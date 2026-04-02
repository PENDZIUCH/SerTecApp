<?php
/**
 * backup_d1.php — Exporta la D1 de produccion a SQL restaurable
 * Uso: php backup_d1.php
 * Requiere: wrangler instalado y autenticado
 */

$outputDir = __DIR__ . '/backups';
$fecha = date('Y-m-d_H-i');
$outputFile = "$outputDir/sertecapp_backup_$fecha.sql";

if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

$tables = [
    'roles', 'users', 'user_roles', 'customers',
    'equipment_brands', 'equipment_models', 'equipments',
    'parts', 'work_orders', 'wo_parts_used',
    'work_order_partes', 'parte_repuestos', 'work_order_logs',
];

$sql = [];
$sql[] = "-- SerTecApp D1 Backup";
$sql[] = "-- Fecha: $fecha";
$sql[] = "-- Para restaurar: aplicar schema.sql primero, luego este archivo";
$sql[] = "";
$sql[] = "PRAGMA foreign_keys = OFF;";
$sql[] = "";

$totalRows = 0;

foreach ($tables as $table) {
    echo "Exportando $table...\n";

    // Consultar via wrangler
    $cmd = "cd \"" . __DIR__ . "/sertecapp-worker\" && npx wrangler d1 execute sertecapp-db --remote --command=\"SELECT * FROM $table\" --json 2>nul";
    $output = shell_exec($cmd);

    if (!$output) {
        $sql[] = "-- SKIP: $table (sin datos o error)";
        $sql[] = "";
        continue;
    }

    // El output de wrangler --json tiene formato especial
    $decoded = json_decode($output, true);
    $rows = $decoded[0]['results'] ?? [];

    if (empty($rows)) {
        $sql[] = "-- $table: vacía";
        $sql[] = "";
        continue;
    }

    $sql[] = "-- $table (" . count($rows) . " registros)";
    foreach ($rows as $row) {
        $cols = implode(', ', array_keys($row));
        $vals = implode(', ', array_map(function($v) {
            if ($v === null) return 'NULL';
            return "'" . str_replace("'", "''", (string)$v) . "'";
        }, array_values($row)));
        $sql[] = "INSERT OR IGNORE INTO $table ($cols) VALUES ($vals);";
        $totalRows++;
    }
    $sql[] = "";
    echo "  -> " . count($rows) . " registros\n";
}

$sql[] = "PRAGMA foreign_keys = ON;";
$sql[] = "-- Total: $totalRows registros";

file_put_contents($outputFile, implode("\n", $sql));

// Limpiar backups viejos (mantener los últimos 10)
$backups = glob("$outputDir/sertecapp_backup_*.sql");
rsort($backups);
foreach (array_slice($backups, 10) as $old) {
    unlink($old);
    echo "Backup viejo eliminado: " . basename($old) . "\n";
}

echo "\n✅ Backup completado: $outputFile\n";
echo "Total: $totalRows registros\n";

// También guardar una copia del schema actual
copy(__DIR__ . '/sertecapp-worker/src/db/schema.sql', "$outputDir/schema_$fecha.sql");
echo "Schema guardado también.\n";
