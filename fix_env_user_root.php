<?php
/**
 * Script para cambiar el usuario MySQL de nexusyl_nexusmk a nexusyl_root
 * El usuario nexusyl_nexusmk fue eliminado de MySQL, ahora solo existe nexusyl_root
 */
$possibleFiles = [
    __DIR__ . '/.env',
    __DIR__ . '/backend/.env',
];

$envFile = null;
foreach ($possibleFiles as $f) {
    if (file_exists($f)) {
        $envFile = $f;
        break;
    }
}

if (!$envFile) {
    die("ERROR: No se encontró .env\n");
}

echo "=== CORRIGIENDO USUARIO MySQL EN .env ===\n";
echo "Archivo: $envFile\n\n";

$content = file_get_contents($envFile);
echo "=== CONTENIDO ORIGINAL ===\n" . $content . "\n\n";

// Normalizar saltos de línea
$content = str_replace("\r\n", "\n", $content);

$lines = explode("\n", $content);
$changed = false;
foreach ($lines as $i => $line) {
    if (preg_match('/^NEXUSMK_DB_USER=nexusyl_nexusmk$/i', $line)) {
        echo "Cambiando línea $i: '$line' -> 'NEXUSMK_DB_USER=nexusyl_root'\n";
        $lines[$i] = 'NEXUSMK_DB_USER=nexusyl_root';
        $changed = true;
    }
    if (preg_match('/^MYSQL_USER=nexusyl_nexusmk$/i', $line)) {
        echo "Cambiando línea $i: '$line' -> 'MYSQL_USER=nexusyl_root'\n";
        $lines[$i] = 'MYSQL_USER=nexusyl_root';
        $changed = true;
    }
}

if (!$changed) {
    echo "ERROR: No se encontraron las variables NEXUSMK_DB_USER o MYSQL_USER con valor nexusyl_nexusmk\n";
    exit(1);
}

$newContent = implode("\n", $lines);
file_put_contents($envFile, $newContent);

echo "\n=== CONTENIDO ACTUALIZADO ===\n" . $newContent . "\n";
echo "=== HECHO ===\n";
echo "Se actualizó NEXUSMK_DB_USER y MYSQL_USER a 'nexusyl_root'\n";
