<?php
/**
 * Concede permisos a nexusyl_root sobre nexusyl_nexusmk
 * y también crea la base de datos 'nexusmk' si no existe
 */
$host = 'localhost';
$user = 'nexusyl_root';
$pass = 'Casita.2026';

echo "=== CONCEDIENDO PERMISOS ===\n\n";

try {
    $mysqli = new mysqli($host, $user, $pass);
    if ($mysqli->connect_error) {
        die("ERROR: " . $mysqli->connect_error . "\n");
    }
    echo "✅ Conectado como nexusyl_root\n\n";

    // 1. Conceder permisos sobre nexusyl_nexusmk (la BD que existe)
    echo "1. Concediendo permisos sobre nexusyl_nexusmk...\n";
    $mysqli->query("GRANT ALL PRIVILEGES ON nexusyl_nexusmk.* TO 'nexusyl_root'@'localhost'");
    echo "   ✅ Permisos concedidos sobre nexusyl_nexusmk.*\n";

    // 2. Crear base de datos nexusmk (para compatibilidad con authController)
    echo "2. Creando base de datos nexusmk...\n";
    $mysqli->query("CREATE DATABASE IF NOT EXISTS nexusmk");
    echo "   ✅ Base de datos nexusmk creada\n";
    
    // 3. Conceder permisos sobre nexusmk
    echo "3. Concediendo permisos sobre nexusmk...\n";
    $mysqli->query("GRANT ALL PRIVILEGES ON nexusmk.* TO 'nexusyl_root'@'localhost'");
    echo "   ✅ Permisos concedidos sobre nexusmk.*\n";

    $mysqli->query("FLUSH PRIVILEGES");
    echo "\n✅ Privilegios actualizados\n";
    
    $mysqli->close();
    
    echo "\n=== HECHO ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
