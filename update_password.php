<?php
// Script para actualizar la contraseña del admin
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Hash generado: " . $hash . "\n";
echo "Verificación: " . (password_verify($password, $hash) ? 'OK' : 'FAIL') . "\n";

// Conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'sertecapp');

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$stmt = $conn->prepare("UPDATE usuarios SET password_hash = ? WHERE email = 'admin@sertecapp.com'");
$stmt->bind_param("s", $hash);

if ($stmt->execute()) {
    echo "✅ Contraseña actualizada correctamente\n";
} else {
    echo "❌ Error al actualizar: " . $conn->error . "\n";
}

$stmt->close();
$conn->close();
