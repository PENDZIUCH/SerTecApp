<?php
// Migración SQLite → MySQL para SerTecApp
// Ejecutar: php migrate_sqlite_to_mysql.php

$sqlitePath = __DIR__ . '/database/database.sqlite';
$mysqlHost  = '127.0.0.1';
$mysqlDb    = 'sertecapp';
$mysqlUser  = 'root';
$mysqlPass  = '';

echo "=== SerTecApp: SQLite → MySQL ===\n\n";

// Conectar SQLite
$sqlite = new SQLite3($sqlitePath);
$sqlite->busyTimeout(5000);
echo "[OK] SQLite conectado\n";

// Conectar MySQL
$mysql = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDb;charset=utf8mb4", $mysqlUser, $mysqlPass);
$mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
echo "[OK] MySQL conectado\n\n";

function migrateTable($sqlite, $mysql, $table, $columns, $truncate = true) {
    if ($truncate) {
        $mysql->exec("SET FOREIGN_KEY_CHECKS=0");
        $mysql->exec("TRUNCATE TABLE `$table`");
        $mysql->exec("SET FOREIGN_KEY_CHECKS=1");
    }

    $colList = implode(', ', array_map(fn($c) => "`$c`", $columns));
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $stmt = $mysql->prepare("INSERT IGNORE INTO `$table` ($colList) VALUES ($placeholders)");

    $res = $sqlite->query("SELECT * FROM $table");
    $count = 0;
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $values = array_map(fn($c) => isset($row[$c]) ? $row[$c] : null, $columns);
        $stmt->execute($values);
        $count++;
    }
    echo "[OK] $table: $count registros migrados\n";
}

// 1. Roles
migrateTable($sqlite, $mysql, 'roles', ['id','name','guard_name','created_at','updated_at']);

// 2. Permissions
migrateTable($sqlite, $mysql, 'permissions', ['id','name','guard_name','created_at','updated_at']);

// 3. Users
migrateTable($sqlite, $mysql, 'users', [
    'id','name','email','password','phone','avatar_url','job_title',
    'is_active','last_login_at','email_verified_at','remember_token',
    'created_at','updated_at','deleted_at'
]);

// 4. model_has_roles
migrateTable($sqlite, $mysql, 'model_has_roles', ['role_id','model_type','model_id']);

// 5. role_has_permissions
migrateTable($sqlite, $mysql, 'role_has_permissions', ['permission_id','role_id']);

// 6. model_has_permissions
migrateTable($sqlite, $mysql, 'model_has_permissions', ['permission_id','model_type','model_id']);

// 7. Customers (sin 'notes' que no existe en MySQL)
migrateTable($sqlite, $mysql, 'customers', [
    'id','customer_type','business_name','first_name','last_name','email',
    'secondary_email','phone','tax_id','address','city','state','country',
    'postal_code','is_active','created_at','updated_at','deleted_at'
]);

// 8. Parts (repuestos)
migrateTable($sqlite, $mysql, 'parts', [
    'id','part_number','name','sku','description','unit_cost','stock_qty',
    'stock_quantity','min_stock_level','location','fob_price_usd','markup_percent',
    'sale_price_usd','equipment_model_id','is_active','created_at','updated_at','deleted_at'
]);

// 9. Work Orders
migrateTable($sqlite, $mysql, 'work_orders', [
    'id','customer_id','equipment_id','wo_number','title','description','priority',
    'status','assigned_tech_id','scheduled_date','scheduled_time','started_at',
    'completed_at','labor_cost','parts_cost','total_cost','requires_signature',
    'signature_token','signature_image_path','signed_at','created_by','updated_by',
    'created_at','updated_at','deleted_at'
]);

// 10. Agregar/actualizar super_admin role y asignarlo a pendziuch@gmail.com
echo "\n[+] Configurando super_admin...\n";
$mysql->exec("INSERT IGNORE INTO roles (name, guard_name, created_at, updated_at) VALUES ('super_admin','web',NOW(),NOW())");
$superAdminId = $mysql->query("SELECT id FROM roles WHERE name='super_admin'")->fetchColumn();
$hugoId = $mysql->query("SELECT id FROM users WHERE email='pendziuch@gmail.com'")->fetchColumn();
if ($hugoId && $superAdminId) {
    $mysql->exec("INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id) VALUES ($superAdminId,'App\\\\Models\\\\User',$hugoId)");
    echo "[OK] super_admin asignado a pendziuch@gmail.com\n";
}

echo "\n=== MIGRACIÓN COMPLETA ===\n";

// Verificación final
foreach (['users','customers','parts','work_orders','roles'] as $t) {
    $n = $mysql->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo "  $t: $n\n";
}
