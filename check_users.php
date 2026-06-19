<?php
/**
 * Verifica qué usuarios MySQL existen
 */
$host = 'localhost';

// Probar diferentes usuarios
$credentials = [
    ['nexusyl_root', 'Casita.2026'],
    ['nexusyl_nexusmk', 'Casita.20'],
    ['nexusyl_nexusmk', 'Casita.2026'],
    ['root', ''],
    ['root', 'Casita.2026'],
];

echo "=== VERIFICANDO USUARIOS MySQL ===\n\n";

foreach ($credentials as $cred) {
    $user = $cred[0];
    $pass = $cred[1];
    try {
        $mysqli = @new mysqli($host, $user, $pass);
        if (!$mysqli->connect_error) {
            echo "✅ $user / $pass -> CONECTA\n";
            
            // Verificar si puede hacer GRANT
            $result = $mysqli->query("SHOW GRANTS");
            if ($result) {
                echo "   Grants:\n";
                while ($row = $result->fetch_row()) {
                    echo "   - {$row[0]}\n";
                }
            }
            
            $mysqli->close();
        } else {
            echo "❌ $user / $pass -> {$mysqli->connect_error}\n";
        }
    } catch (Exception $e) {
        echo "❌ $user / $pass -> Error\n";
    }
}
