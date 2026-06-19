<?php
echo "=== DIAGNÓSTICO DE CONEXIÓN MySQL ===\n\n";

// 1. Verificar extensión mysqli
echo "[1] Extensión mysqli: " . (extension_loaded('mysqli') ? 'DISPONIBLE' : 'NO DISPONIBLE') . "\n";

// 2. Intentar conexión con las credenciales del .env
$host = 'localhost';
$user = 'nexusyl_nexusmk';
$pass = '';
$db = 'nexusyl_nexusmk';

echo "[2] Intentando conectar a MySQL...\n";
echo "    Host: $host\n";
echo "    User: $user\n";
echo "    Password: '" . ($pass ?: '(vacía)') . "'\n";
echo "    Database: $db\n";

$conn = @new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo "    ERROR: " . $conn->connect_error . "\n";
    
    // Intentar sin seleccionar base de datos
    echo "[3] Intentando conectar sin seleccionar DB...\n";
    $conn2 = @new mysqli($host, $user, $pass);
    if ($conn2->connect_error) {
        echo "    ERROR: " . $conn2->connect_error . "\n";
    } else {
        echo "    CONEXIÓN EXITOSA (sin DB)\n";
        
        // Listar bases de datos
        $result = $conn2->query("SHOW DATABASES LIKE 'nexusyl%'");
        echo "[4] Bases de datos encontradas:\n";
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_row()) {
                echo "    - " . $row[0] . "\n";
            }
        } else {
            echo "    Ninguna base de datos nexusyl_* encontrada\n";
        }
        
        // Verificar si el usuario existe
        $result2 = $conn2->query("SELECT USER(), CURRENT_USER()");
        if ($result2) {
            $row = $result2->fetch_row();
            echo "[5] Usuario actual: " . $row[0] . " (efectivo: " . $row[1] . ")\n";
        }
        
        $conn2->close();
    }
} else {
    echo "    CONEXIÓN EXITOSA\n";
    
    // Listar tablas
    $result = $conn->query("SHOW TABLES");
    echo "[3] Tablas en $db:\n";
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_row()) {
            echo "    - " . $row[0] . "\n";
        }
    } else {
        echo "    (ninguna tabla)\n";
    }
    
    $conn->close();
}

echo "\n=== DIAGNÓSTICO COMPLETADO ===\n";
?>