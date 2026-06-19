<?php
// Script para encontrar la configuración de Node.js Selector
// Buscar archivos de configuración relacionados con Node.js Selector

echo "<pre>\n";
echo "=== Buscando configuración de Node.js Selector ===\n\n";

$searchPaths = [
    '/home/nexusyl/.nodejs',
    '/home/nexusyl/.cpanel',
    '/home/nexusyl/.passenger',
    '/etc/cpanel',
    '/usr/local/cpanel',
];

$searchFiles = [
    '/home/nexusyl/.cpanel/passenger.conf',
    '/home/nexusyl/.cpanel/nodejs.conf',
    '/home/nexusyl/.cpanel/nodejs.json',
    '/home/nexusyl/.nodejs/config.json',
    '/home/nexusyl/.nodejs/versions.json',
    '/etc/cpanel/passenger.conf',
    '/etc/cpanel/nodejs.conf',
    '/usr/local/cpanel/passenger.conf',
];

echo "Buscando archivos de configuración...\n\n";

foreach ($searchFiles as $file) {
    if (file_exists($file)) {
        echo "✅ ENCONTRADO: $file\n";
        echo "   Tamaño: " . filesize($file) . " bytes\n";
        echo "   Contenido:\n";
        echo "   --- INICIO ---\n";
        $content = file_get_contents($file);
        // Mostrar solo si es texto
        if (strlen($content) < 5000) {
            echo "   " . str_replace("\n", "\n   ", $content) . "\n";
        } else {
            echo "   (archivo muy grande, mostrando primeros 2000 chars)\n";
            echo "   " . str_replace("\n", "\n   ", substr($content, 0, 2000)) . "\n";
        }
        echo "   --- FIN ---\n\n";
    } else {
        echo "❌ No encontrado: $file\n";
    }
}

echo "\nBuscando directorios de Node.js Selector...\n\n";

foreach ($searchPaths as $path) {
    if (is_dir($path)) {
        echo "✅ Directorio encontrado: $path\n";
        $files = scandir($path);
        foreach ($files as $f) {
            if ($f != '.' && $f != '..') {
                $fp = $path . '/' . $f;
                echo "   - $f (" . (is_dir($fp) ? 'directorio' : filesize($fp) . ' bytes') . ")\n";
            }
        }
        echo "\n";
    } else {
        echo "❌ No encontrado: $path\n";
    }
}

echo "\nBuscando en /home/nexusyl/ archivos ocultos...\n";
$homeFiles = scandir('/home/nexusyl/');
foreach ($homeFiles as $f) {
    if ($f != '.' && $f != '..' && $f[0] == '.') {
        $fp = '/home/nexusyl/' . $f;
        echo "   $f (" . (is_dir($fp) ? 'directorio' : filesize($fp) . ' bytes') . ")\n";
    }
}

echo "\n=== Búsqueda completada ===\n";
echo "</pre>\n";
