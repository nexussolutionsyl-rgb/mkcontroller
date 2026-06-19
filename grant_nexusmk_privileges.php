<?php
/**
 * Script para conceder permisos a nexusyl_root sobre la base de datos nexusmk
 * El usuario existe y la contraseña es correcta, pero no tiene acceso a nexusmk
 */

// Intentar conexión sin especificar base de datos
$host = 'localhost';
$user = 'nexusyl_root';
$pass = 'Casita.2026';

echo "=== CONCEDIENDO PERMISOS ===\n";
echo "Usuario: $user\n";
echo "Base de datos: nexusmk\n\n";

try {
    $mysqli = new mysqli($host, $user, $pass);
    
    if ($mysqli->connect_error) {
        die("ERROR de conexión: " . $mysqli->connect_error . "\n");
    }
    
    echo "✅ Conexión establecida con MySQL\n\n";
    
    // Verificar si la base de datos nexusmk existe
    $result = $mysqli->query("SHOW DATABASES LIKE 'nexusmk'");
    if ($result->num_rows > 0) {
        echo "✅ La base de datos 'nexusmk' existe\n";
    } else {
        echo "⚠️ La base de datos 'nexusmk' NO existe. Creándola...\n";
        $mysqli->query("CREATE DATABASE IF NOT EXISTS nexusmk");
        echo "✅ Base de datos 'nexusmk' creada\n";
    }
    
    // Conceder todos los privilegios sobre nexusmk al usuario
    $sql = "GRANT ALL PRIVILEGES ON nexusmk.* TO '$user'@'$host'";
    if ($mysqli->query($sql)) {
        echo "✅ Permisos concedidos: nexusyl_root tiene acceso a nexusmk.*\n";
    } else {
        echo "❌ Error al conceder permisos: " . $mysqli->error . "\n";
        // Intentar con GRANT más específico
        $sql2 = "GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX ON nexusmk.* TO '$user'@'$host'";
        if ($mysqli->query($sql2)) {
            echo "✅ Permisos específicos concedidos\n";
        } else {
            echo "❌ Error: " . $mysqli->error . "\n";
        }
    }
    
    $mysqli->query("FLUSH PRIVILEGES");
    echo "✅ Privilegios actualizados\n";
    
    $mysqli->close();
    
    echo "\n=== HECHO ===\n";
    echo "Ahora nexusyl_root debería poder conectarse a la base de datos 'nexusmk'\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
