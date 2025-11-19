<?php
// Test login
require_once __DIR__ . '/config/database.php';

// Verificar usuario
$db = Database::getInstance();
$user = $db->fetchOne("SELECT * FROM usuarios WHERE email = ?", ['admin@sertecapp.com']);

echo "Usuario encontrado:\n";
print_r($user);

// Verificar password
$password = 'admin123';
$result = password_verify($password, $user['password_hash']);

echo "\n\nPassword verify result: " . ($result ? 'TRUE' : 'FALSE') . "\n";

// Generar nuevo hash
$newHash = password_hash($password, PASSWORD_DEFAULT);
echo "\nNuevo hash para 'admin123':\n$newHash\n";
