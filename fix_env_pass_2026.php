<?php
/**
 * Script para actualizar la contraseña MySQL a Casita.2026
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

echo "=== ACTUALIZANDO CONTRASEÑA MySQL EN .env ===\n";
echo "Archivo: $envFile\n\n";

$content = file_get_contents($envFile);
echo "=== CONTENIDO ORIGINAL ===\n" . $content . "\n\n";

$content = str_replace("\r\n", "\n", $content);

$lines = explode("\n", $content);
$changed = false;
foreach ($lines as $i => $line) {
    if (preg_match('/^NEXUSMK_DB_PASSWORD=.*$/i', $line)) {
        echo "Cambiando línea $i: '$line' -> 'NEXUSMK_DB_PASSWORD=Casita.2026'\n";
        $lines[$i] = 'NEXUSMK_DB_PASSWORD=Casita.2026';
        $changed = true;
    }
    if (preg_match('/^MYSQL_PASSWORD=.*$/i', $line)) {
        echo "Cambiando línea $i: '$line' -> 'MYSQL_PASSWORD=Casita.2026'\n";
        $lines[$i] = 'MYSQL_PASSWORD=Casita.2026';
        $changed = true;
    }
}

if (!$changed) {
    echo "ERROR: No se encontraron las variables de contraseña\n";
    exit(1);
}

$newContent = implode("\n", $lines);
file_put_contents($envFile, $newContent);

echo "\n=== CONTENIDO ACTUALIZADO ===\n" . $newContent . "\n";
echo "=== HECHO ===\n";
echo "Se actualizó la contraseña MySQL a 'Casita.2026'\n";
