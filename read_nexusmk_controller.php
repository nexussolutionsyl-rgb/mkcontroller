<?php
/**
 * Lee el contenido del nexusmkController.js en el servidor
 * para ver exactamente qué configuración DB tiene
 */
header('Content-Type: text/plain; charset=utf-8');

$filePath = '/home/nexusyl/nexusmk.nexussolutionsyl.com/backend/controllers/nexusmkController.js';

if (!file_exists($filePath)) {
    die("ERROR: Archivo no encontrado en $filePath\n");
}

$content = file_get_contents($filePath);
echo "=== ARCHIVO: $filePath ===\n";
echo "Tamano: " . strlen($content) . " bytes\n\n";

// Buscar la configuracion DB
$pattern = '/const DB_CONFIG\s*=\s*\{[^}]+\}/';
if (preg_match($pattern, $content, $matches)) {
    echo "=== DB_CONFIG ENCONTRADO ===\n";
    echo $matches[0] . "\n\n";
} else {
    echo "=== DB_CONFIG NO ENCONTRADO con regex ===\n";
}

// Buscar lineas con DB_CONFIG
$lines = explode("\n", $content);
echo "=== LINEAS CON 'DB_CONFIG' ===\n";
foreach ($lines as $i => $line) {
    if (stripos($line, 'DB_CONFIG') !== false || stripos($line, 'nexusyl') !== false || stripos($line, 'password') !== false) {
        echo "Linea " . ($i+1) . ": " . $line . "\n";
    }
}

echo "\n=== LINEAS 14-25 ===\n";
for ($i = 13; $i < min(25, count($lines)); $i++) {
    echo ($i+1) . ": " . $lines[$i] . "\n";
}
