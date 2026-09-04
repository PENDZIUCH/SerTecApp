<?php
/**
 * export_for_d1.php — v2
 * Exporta tablas de Laravel SQLite a SQL compatible con Cloudflare D1.
 */

$dbPath    = __DIR__ . '/database/database.sqlite';
$outputPath = __DIR__ . '/../sertecapp-worker/scripts/export_clean.sql';

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Columnas permitidas por tabla destino (las del schema D1)
$allowedCols = [
    'roles'              => ['id','name','created_at','updated_at'],
    'users'              => ['id','name','email','password','phone','job_title','is_active','last_login_at','created_at','updated_at'],
    'user_roles'         => ['user_id','role_id'],
    'customers'          => ['id','customer_type','business_name','first_name','last_name','email','phone','secondary_email','tax_id','address','city','state','country','postal_code','is_active','notes','created_at','updated_at'],
    'equipment_brands'   => ['id','name','created_at'],
    'equipment_models'   => ['id','brand_id','name','created_at'],
    'equipments'         => ['id','customer_id','brand_id','model_id','serial_number','equipment_code','purchase_date','warranty_expiration','next_service_date','last_service_date','location','status','notes','created_at','updated_at'],
    'parts'              => ['id','part_number','name','sku','description','unit_cost','stock_qty','min_stock_level','location','fob_price_usd','markup_percent','sale_price_usd','equipment_model_id','is_active','created_at','updated_at'],
    'work_orders'        => ['id','customer_id','equipment_id','wo_number','title','description','priority','status','assigned_tech_id','scheduled_date','scheduled_time','started_at','completed_at','labor_cost','parts_cost','total_cost','requires_signature','created_by','created_at','updated_at'],
    'wo_parts_used'      => ['id','work_order_id','part_id','part_name','quantity','unit_cost','total_cost','created_at'],
    'work_order_partes'  => ['id','work_order_id','tecnico_id','diagnostico','trabajo_realizado','firma_base64','fotos','synced','created_at','updated_at'],
    'work_order_logs'    => ['id','work_order_id','log_type','message','created_by','created_at'],
];

// [tabla_origen => tabla_destino]
$tables = [
    'roles'            => 'roles',
    'users'            => 'users',
    'model_has_roles'  => 'user_roles',
    'customers'        => 'customers',
    'equipment_brands' => 'equipment_brands',
    'equipment_models' => 'equipment_models',
    'equipments'       => 'equipments',
    'parts'            => 'parts',
    'work_orders'      => 'work_orders',
    'wo_parts_used'    => 'wo_parts_used',
    'work_parts'       => 'work_order_partes',
    'work_order_logs'  => 'work_order_logs',
];

$out = [];
$out[] = "-- SerTecApp D1 Export v2";
$out[] = "-- Generado: " . date('Y-m-d H:i:s');
$out[] = "-- Aplicar DESPUES de schema.sql y seed_roles.sql";
$out[] = "";
$out[] = "PRAGMA foreign_keys = OFF;";
$out[] = "";

$totalRows = 0;

foreach ($tables as $srcTable => $dstTable) {
    $exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$srcTable'")->fetch();
    if (!$exists) { $out[] = "-- SKIP: '$srcTable' no existe\n"; continue; }

    $rows = $pdo->query("SELECT * FROM $srcTable")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) { $out[] = "-- SKIP: '$srcTable' vacía\n"; continue; }

    $out[] = "-- $dstTable (" . count($rows) . " registros)";
    $allowed = $allowedCols[$dstTable] ?? null;

    foreach ($rows as $row) {
        // Passwords
        if (isset($row['password'])) {
            $row['password'] = str_replace('$2y$', '$2b$', $row['password']);
        }

        // Mapeos especiales
        if ($srcTable === 'model_has_roles') {
            if (empty($row['model_id']) || empty($row['role_id'])) continue;
            if (isset($row['model_type']) && strpos($row['model_type'], 'User') === false) continue;
            $row = ['user_id' => $row['model_id'], 'role_id' => $row['role_id']];
        }

        if ($srcTable === 'work_parts') {
            $row = [
                'id'                => $row['id'] ?? null,
                'work_order_id'     => $row['work_order_id'] ?? null,
                'tecnico_id'        => $row['technician_id'] ?? null,
                'diagnostico'       => $row['diagnosis'] ?? null,
                'trabajo_realizado' => $row['work_done'] ?? null,
                'firma_base64'      => $row['signature'] ?? null,
                'fotos'             => null,
                'synced'            => 1,
                'created_at'        => $row['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at'        => $row['updated_at'] ?? date('Y-m-d H:i:s'),
            ];
        }

        // Filtrar solo columnas permitidas
        if ($allowed) {
            $row = array_intersect_key($row, array_flip($allowed));
        }

        if (empty($row)) continue;

        $cols = implode(', ', array_keys($row));
        $vals = implode(', ', array_map(function($v) {
            if ($v === null) return 'NULL';
            return "'" . str_replace("'", "''", (string)$v) . "'";
        }, array_values($row)));

        $out[] = "INSERT OR IGNORE INTO $dstTable ($cols) VALUES ($vals);";
        $totalRows++;
    }
    $out[] = "";
}

$out[] = "PRAGMA foreign_keys = ON;";
$out[] = "-- Total: $totalRows registros";

file_put_contents($outputPath, implode("\n", $out));
echo "✅ Exportado: $totalRows registros → scripts/export_clean.sql\n\n";
foreach ($tables as $src => $dst) {
    $e = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$src'")->fetch();
    if (!$e) { echo "   - $src: NO EXISTE\n"; continue; }
    $n = $pdo->query("SELECT COUNT(*) FROM $src")->fetchColumn();
    echo "   - $src → $dst: $n filas\n";
}
