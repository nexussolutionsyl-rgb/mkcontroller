<?php
/**
 * Script simple para actualizar nexusmkController.js
 * Usa credenciales de root directamente
 */
header('Content-Type: text/plain; charset=utf-8');

$root_pass = 'Casita.2026';
$file = __DIR__ . '/backend/controllers/nexusmkController.js';

if (!file_exists($file)) {
    die("ERROR: Archivo no encontrado: $file\n");
}

$content = file_get_contents($file);
echo "Archivo leido: " . strlen($content) . " bytes\n";

// Nuevo DB_CONFIG con root
$new_config = "const DB_CONFIG = {
    host: 'localhost',
    user: 'nexusyl_root',
    password: '$root_pass',
    database: 'nexusyl_nexusmk'
};";

// Reemplazar usando regex
$pattern = '/const DB_CONFIG\s*=\s*\{[^}]+\};/s';
if (preg_match($pattern, $content, $matches)) {
    echo "Patron encontrado: " . substr($matches[0], 0, 80) . "...\n";
    $new_content = preg_replace($pattern, $new_config, $content);
    
    if ($new_content === $content) {
        die("ERROR: El reemplazo no cambio nada\n");
    }
    
    $bytes = file_put_contents($file, $new_content);
    if ($bytes === false) {
        die("ERROR: No se pudo escribir\n");
    }
    echo "OK: Archivo actualizado ($bytes bytes)\n";
} else {
    die("ERROR: No se encontro DB_CONFIG\n");
}

// Verificar
$verify = file_get_contents($file);
if (strpos($verify, 'nexusyl_root') !== false) {
    echo "OK: Verificacion exitosa\n";
} else {
    die("ERROR: Verificacion fallo\n");
}

// Probar conexion MySQL
echo "\n--- Probando conexion MySQL ---\n";
$conn = @new mysqli('localhost', 'nexusyl_root', $root_pass, 'nexusyl_nexusmk');
if ($conn->connect_error) {
    die("ERROR MySQL: " . $conn->connect_error . "\n");
}
echo "OK: Conexion MySQL exitosa!\n";

$result = $conn->query("SHOW TABLES");
if ($result) {
    echo "Tablas:\n";
    $count = 0;
    while ($row = $result->fetch_array()) {
        echo "  - " . $row[0] . "\n";
        $count++;
    }
    if ($count === 0) echo "  (vacia)\n";
}
$conn->close();
echo "\n=== LISTO ===\n";
?>
