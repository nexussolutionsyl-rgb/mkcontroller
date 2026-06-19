<?php
/**
 * Script para corregir NEXUSMK_DB_NAME y MYSQL_DATABASE en el .env
 * La base de datos correcta es 'nexusmk' (no 'nexusyl_nexusmk')
 * porque authController.js se conecta a 'nexusmk' (hardcoded) y funciona
 * 
 * El .env que usa Node.js está en la RAÍZ del proyecto, no en backend/
 */
$possibleFiles = [
    __DIR__ . '/.env',           // /home/nexusyl/nexusmk.nexussolutionsyl.com/.env
    __DIR__ . '/backend/.env',   // fallback
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

echo "=== CORRIGIENDO .env ===\n";
echo "Archivo: $envFile\n\n";

$content = file_get_contents($envFile);
echo "=== CONTENIDO ORIGINAL ===\n" . $content . "\n\n";

// Normalizar saltos de línea a \n
$content = str_replace("\r\n", "\n", $content);

// Reemplazar línea por línea
$lines = explode("\n", $content);
$changed = false;
foreach ($lines as $i => $line) {
    if (preg_match('/^NEXUSMK_DB_NAME=.*$/i', $line)) {
        echo "Cambiando línea $i: '$line' -> 'NEXUSMK_DB_NAME=nexusmk'\n";
        $lines[$i] = 'NEXUSMK_DB_NAME=nexusmk';
        $changed = true;
    }
    if (preg_match('/^MYSQL_DATABASE=.*$/i', $line)) {
        echo "Cambiando línea $i: '$line' -> 'MYSQL_DATABASE=nexusmk'\n";
        $lines[$i] = 'MYSQL_DATABASE=nexusmk';
        $changed = true;
    }
}

if (!$changed) {
    echo "ADVERTENCIA: No se encontraron las variables para reemplazar.\n";
    echo "Agregándolas al final...\n";
    $lines[] = 'NEXUSMK_DB_NAME=nexusmk';
    $lines[] = 'MYSQL_DATABASE=nexusmk';
}

$newContent = implode("\n", $lines);
file_put_contents($envFile, $newContent);

echo "\n=== CONTENIDO ACTUALIZADO ===\n" . $newContent . "\n";
echo "=== HECHO ===\n";
echo "Se actualizó NEXUSMK_DB_NAME y MYSQL_DATABASE a 'nexusmk'\n";
