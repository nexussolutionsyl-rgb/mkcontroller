<?php
/**
 * Actualiza backend/.env con las credenciales correctas de MySQL
 * start.js carga dotenv desde ./backend/.env
 */
header('Content-Type: text/plain; charset=utf-8');

$appDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$envFile = $appDir . '/backend/.env';

echo "=== ACTUALIZANDO backend/.env ===\n\n";

if (!file_exists($envFile)) {
    echo "ERROR: No existe $envFile\n";
    exit(1);
}

// Leer contenido actual
$content = file_get_contents($envFile);
$lines = explode("\n", $content);
$newLines = [];
$updated = [];

$replacements = [
    'NEXUSMK_DB_HOST' => 'localhost',
    'NEXUSMK_DB_USER' => 'nexusyl_root',
    'NEXUSMK_DB_PASSWORD' => 'Casita.2026',
    'NEXUSMK_DB_NAME' => 'nexusyl_nexusmk',
    'MYSQL_HOST' => 'localhost',
    'MYSQL_USER' => 'nexusyl_root',
    'MYSQL_PASSWORD' => 'Casita.2026',
    'MYSQL_DATABASE' => 'nexusyl_nexusmk'
];

foreach ($lines as $line) {
    $trimmed = trim($line);
    $replaced = false;
    
    foreach ($replacements as $key => $value) {
        if (str_starts_with($trimmed, $key . '=')) {
            $newLines[] = "$key=$value";
            $updated[] = $key;
            $replaced = true;
            break;
        }
    }
    
    if (!$replaced) {
        $newLines[] = $line;
    }
}

// Agregar variables que no existían
foreach ($replacements as $key => $value) {
    if (!in_array($key, $updated)) {
        $newLines[] = "$key=$value";
        $updated[] = $key;
        echo "  [NUEVO] $key=$value\n";
    } else {
        echo "  [ACTUALIZADO] $key=$value\n";
    }
}

$newContent = implode("\n", $newLines);
file_put_contents($envFile, $newContent);

echo "\n✅ backend/.env actualizado correctamente\n";
echo "\n--- Contenido final ---\n";
foreach (explode("\n", $newContent) as $line) {
    $line = trim($line);
    if ($line && !str_starts_with($line, '#')) {
        echo "  $line\n";
    }
}

echo "\n=== FIN ===\n";
