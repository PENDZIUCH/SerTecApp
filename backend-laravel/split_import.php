<?php
// split_import.php — divide export_clean.sql en un archivo por tabla
$sql = file_get_contents(__DIR__ . '/../sertecapp-worker/scripts/export_clean.sql');
$out = __DIR__ . '/../sertecapp-worker/scripts/';

$tables = ['roles','users','user_roles','customers','equipment_brands','equipment_models','equipments','parts','work_orders','wo_parts_used','work_order_partes'];

foreach ($tables as $t) {
    preg_match_all('/INSERT OR IGNORE INTO ' . $t . ' \([^)]+\) VALUES \([^;]+\);/m', $sql, $m);
    if (!empty($m[0])) {
        file_put_contents($out . 'import_' . $t . '.sql', implode("\n", $m[0]) . "\n");
        echo count($m[0]) . " inserts → import_{$t}.sql\n";
    } else {
        echo "0 inserts para $t\n";
    }
}
echo "Listo.\n";
