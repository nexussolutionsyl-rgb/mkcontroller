<?php
/**
 * Script para listar las bases de datos MySQL disponibles
 */
$host = 'localhost';
$user = 'nexusyl_root';
$pass = 'Casita.2026';

echo "=== LISTANDO BASES DE DATOS ===\n";
echo "Usuario: $user\n\n";

try {
    $mysqli = new mysqli($host, $user, $pass);
    
    if ($mysqli->connect_error) {
        die("ERROR de conexión: " . $mysqli->connect_error . "\n");
    }
    
    echo "✅ Conexión establecida\n\n";
    
    // Listar bases de datos
    $result = $mysqli->query("SHOW DATABASES");
    echo "=== BASES DE DATOS DISPONIBLES ===\n";
    $found = false;
    while ($row = $result->fetch_assoc()) {
        $db = $row['Database'];
        if ($db != 'information_schema' && $db != 'performance_schema' && $db != 'mysql' && $db != 'sys') {
            echo "  - $db\n";
            $found = true;
        }
    }
    if (!$found) {
        echo "  (ninguna base de datos de usuario encontrada)\n";
    }
    
    // Verificar si nexusyl_nexusmk existe
    $check = $mysqli->query("SHOW DATABASES LIKE 'nexusyl_nexusmk'");
    if ($check->num_rows > 0) {
        echo "\n✅ La base de datos 'nexusyl_nexusmk' EXISTE\n";
    } else {
        echo "\n❌ La base de datos 'nexusyl_nexusmk' NO existe\n";
    }
    
    $check2 = $mysqli->query("SHOW DATABASES LIKE 'nexusmk'");
    if ($check2->num_rows > 0) {
        echo "✅ La base de datos 'nexusmk' EXISTE\n";
    } else {
        echo "❌ La base de datos 'nexusmk' NO existe\n";
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
