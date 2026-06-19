<?php
echo "<pre>\n";
echo "=== Buscando configuración de Node.js Selector (2) ===\n\n";

// Revisar .cl.selector
$clSelector = '/home/nexusyl/.cl.selector';
if (is_dir($clSelector)) {
    echo "📁 .cl.selector contents:\n";
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($clSelector));
    foreach ($files as $f) {
        if ($f->isFile()) {
            echo "   " . $f->getPathname() . " (" . $f->getSize() . " bytes)\n";
            if ($f->getSize() < 50000) {
                $content = file_get_contents($f->getPathname());
                echo "   --- INICIO ---\n";
                echo "   " . str_replace("\n", "\n   ", $content) . "\n";
                echo "   --- FIN ---\n\n";
            }
        }
    }
}

// Revisar .cpanel/datastore
$datastore = '/home/nexusyl/.cpanel/datastore';
if (is_dir($datastore)) {
    echo "\n📁 .cpanel/datastore contents:\n";
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($datastore));
    foreach ($files as $f) {
        if ($f->isFile()) {
            echo "   " . $f->getPathname() . " (" . $f->getSize() . " bytes)\n";
            if ($f->getSize() < 50000 && $f->getExtension() != 'sqlite') {
                $content = file_get_contents($f->getPathname());
                echo "   --- INICIO ---\n";
                echo "   " . str_replace("\n", "\n   ", $content) . "\n";
                echo "   --- FIN ---\n\n";
            }
        }
    }
}

// Revisar .cpanel/nvdata
$nvdata = '/home/nexusyl/.cpanel/nvdata';
if (is_dir($nvdata)) {
    echo "\n📁 .cpanel/nvdata contents:\n";
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($nvdata));
    foreach ($files as $f) {
        if ($f->isFile()) {
            echo "   " . $f->getPathname() . " (" . $f->getSize() . " bytes)\n";
            if ($f->getSize() < 50000) {
                $content = file_get_contents($f->getPathname());
                echo "   --- INICIO ---\n";
                echo "   " . str_replace("\n", "\n   ", $content) . "\n";
                echo "   --- FIN ---\n\n";
            }
        }
    }
}

// Buscar archivos que contengan "nexusmk" en /home/nexusyl/
echo "\n🔍 Buscando archivos que contengan 'nexusmk'...\n";
$searchDirs = ['/home/nexusyl/.cpanel', '/home/nexusyl/.cl.selector'];
foreach ($searchDirs as $dir) {
    if (is_dir($dir)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($files as $f) {
            if ($f->isFile() && $f->getSize() < 100000) {
                $content = file_get_contents($f->getPathname());
                if (strpos($content, 'nexusmk') !== false) {
                    echo "✅ ENCONTRADO en: " . $f->getPathname() . "\n";
                    echo "   --- INICIO ---\n";
                    echo "   " . str_replace("\n", "\n   ", $content) . "\n";
                    echo "   --- FIN ---\n\n";
                }
            }
        }
    }
}

echo "\n=== Búsqueda completada ===\n";
echo "</pre>\n";
