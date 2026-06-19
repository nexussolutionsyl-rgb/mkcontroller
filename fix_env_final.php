<?php
/**
 * Script final para configurar .env correctamente:
 * - Usuario: nexusyl_root
 * - Password: Casita.2026
 * - Base de datos: nexusyl_nexusmk (la que realmente existe)
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

echo "=== CONFIGURACIÓN FINAL DEL .env ===\n";
echo "Archivo: $envFile\n\n";

$content = file_get_contents($envFile);
echo "=== CONTENIDO ORIGINAL ===\n" . $content . "\n\n";

$content = str_replace("\r\n", "\n", $content);

$lines = explode("\n", $content);
$changes = [
    'NEXUSMK_DB_USER' => 'nexusyl_root',
    'NEXUSMK_DB_PASSWORD' => 'Casita.2026',
    'NEXUSMK_DB_NAME' => 'nexusyl_nexusmk',
    'MYSQL_USER' => 'nexusyl_root',
    'MYSQL_PASSWORD' => 'Casita.2026',
    'MYSQL_DATABASE' => 'nexusyl_nexusmk',
];

foreach ($lines as $i => $line) {
    foreach ($changes as $key => $value) {
        if (preg_match('/^' . preg_quote($key, '/') . '=.*$/i', $line)) {
            echo "Cambiando línea $i: '$line' -> '$key=$value'\n";
            $lines[$i] = "$key=$value";
        }
    }
}

$newContent = implode("\n", $lines);
file_put_contents($envFile, $newContent);

echo "\n=== CONTENIDO ACTUALIZADO ===\n" . $newContent . "\n";
echo "=== HECHO ===\n";
