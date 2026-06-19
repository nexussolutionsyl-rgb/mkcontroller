<?php
/**
 * Script para corregir NEXUSMK_DB_NAME y MYSQL_DATABASE en el .env
 * La base de datos correcta es 'nexusmk' (no 'nexusyl_nexusmk')
 * porque authController.js se conecta a 'nexusmk' (hardcoded) y funciona
 */
$envFile = __DIR__ . '/backend/.env';
if (!file_exists($envFile)) {
    $envFile = __DIR__ . '/.env';
}
if (!file_exists($envFile)) {
    die("ERROR: No se encontró .env en " . __DIR__ . "/backend/ ni en " . __DIR__ . "/\n");
}

echo "=== CORRIGIENDO .env ===\n";
echo "Archivo: $envFile\n\n";

$content = file_get_contents($envFile);
echo "=== CONTENIDO ORIGINAL ===\n$content\n\n";

// Reemplazar NEXUSMK_DB_NAME
$content = preg_replace(
    '/^NEXUSMK_DB_NAME=.*$/m',
    'NEXUSMK_DB_NAME=nexusmk',
    $content
);

// Reemplazar MYSQL_DATABASE
$content = preg_replace(
    '/^MYSQL_DATABASE=.*$/m',
    'MYSQL_DATABASE=nexusmk',
    $content
);

file_put_contents($envFile, $content);

echo "=== CONTENIDO ACTUALIZADO ===\n$content\n";
echo "=== HECHO ===\n";
echo "Se actualizó NEXUSMK_DB_NAME y MYSQL_DATABASE a 'nexusmk'\n";
