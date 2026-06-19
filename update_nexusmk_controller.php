<?php
/**
 * Actualiza nexusmkController.js en el servidor
 * para usar las credenciales de root (nexusyl_root / Casita.2026)
 */
header('Content-Type: text/plain; charset=utf-8');

$root_pass = 'Casita.2026';

// Ruta del archivo a modificar
$file = '/home/nexusyl/nexusmk.nexussolutionsyl.com/backend/controllers/nexusmkController.js';

if (!file_exists($file)) {
    echo "ERROR: Archivo no encontrado: $file\n";
    exit(1);
}

$content = file_get_contents($file);
if ($content === false) {
    echo "ERROR: No se pudo leer el archivo\n";
    exit(1);
}

echo "Archivo leido: " . strlen($content) . " bytes\n";

// Reemplazar la configuracion de DB
$old_config = "const DB_CONFIG = {
    host: process.env.NEXUSMK_DB_HOST || 'localhost',
    user: process.env.NEXUSMK_DB_USER || 'nexusyl_nexusmk',
    password: process.env.NEXUSMK_DB_PASSWORD || '',
    database: process.env.NEXUSMK_DB_NAME || 'nexusyl_nexusmk'
};";

$new_config = "const DB_CONFIG = {
    host: 'localhost',
    user: 'nexusyl_root',
    password: '$root_pass',
    database: 'nexusyl_nexusmk'
};";

$new_content = str_replace($old_config, $new_config, $content);

if ($new_content === $content) {
    echo "AVISO: No se encontro el patron de configuracion. Buscando patron alternativo...\n";
    // Buscar cualquier configuracion de DB_CONFIG
    if (preg_match('/const DB_CONFIG\s*=\s*\{[^}]+\};/s', $content, $matches)) {
        echo "Patron encontrado: " . substr($matches[0], 0, 100) . "...\n";
        $new_content = str_replace($matches[0], $new_config, $content);
    } else {
        echo "ERROR: No se pudo encontrar DB_CONFIG en el archivo\n";
        exit(1);
    }
}

$bytes = file_put_contents($file, $new_content);
if ($bytes === false) {
    echo "ERROR: No se pudo escribir el archivo\n";
    exit(1);
}

echo "OK: Archivo actualizado ($bytes bytes)\n";

// Verificar que el cambio se hizo
$verify = file_get_contents($file);
if (strpos($verify, 'nexusyl_root') !== false) {
    echo "OK: Verificacion exitosa - contiene nexusyl_root\n";
} else {
    echo "ERROR: Verificacion fallo\n";
    exit(1);
}

// Ahora probar conexion MySQL directamente desde PHP
echo "\n--- Probando conexion MySQL ---\n";
$conn = @new mysqli('localhost', 'nexusyl_root', $root_pass, 'nexusyl_nexusmk');
if ($conn->connect_error) {
    echo "ERROR MySQL: " . $conn->connect_error . "\n";
    exit(1);
}
echo "OK: Conexion MySQL exitosa!\n";

// Listar tablas
$result = $conn->query("SHOW TABLES");
if ($result) {
    echo "Tablas encontradas:\n";
    $count = 0;
    while ($row = $result->fetch_array()) {
        echo "  - " . $row[0] . "\n";
        $count++;
    }
    if ($count === 0) {
        echo "  (ninguna tabla - la BD existe pero esta vacia)\n";
    }
} else {
    echo "INFO: " . $conn->error . "\n";
}

$conn->close();
echo "\n=== LISTO ===\n";
?>
