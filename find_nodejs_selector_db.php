<?php
echo "=== Buscar registro de Node.js Selector ===\n\n";

// 1. Buscar en /var/cpanel/
echo "[1] Buscando en /var/cpanel/:\n";
$paths = [
    '/var/cpanel',
    '/var/cpanel/nodejs',
    '/var/cpanel/selectors',
];
foreach ($paths as $p) {
    if (is_dir($p)) {
        echo "  [DIR] $p/\n";
        $files = scandir($p);
        foreach ($files as $f) {
            if ($f !== '.' && $f !== '..') {
                $fp = $p . '/' . $f;
                if (is_file($fp)) {
                    echo "    [FILE] $f (" . filesize($fp) . " bytes)\n";
                    if (filesize($fp) < 5000 && (strpos($f, 'node') !== false || strpos($f, 'selector') !== false)) {
                        echo "    ---\n" . file_get_contents($fp) . "\n";
                    }
                } elseif (is_dir($fp)) {
                    echo "    [DIR] $f/\n";
                }
            }
        }
    } else {
        echo "  [NO] $p: NO EXISTE\n";
    }
}

// 2. Buscar archivos .db (SQLite)
echo "\n[2] Buscando bases de datos SQLite:\n";
$dbPaths = [
    '/var/cpanel',
    '/home/nexusyl',
];
foreach ($dbPaths as $dp) {
    if (is_dir($dp)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dp, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'db') {
                $fp = $file->getPathname();
                echo "  [DB] $fp (" . $file->getSize() . " bytes)\n";
            }
        }
    }
}

// 3. Buscar archivos con "nodejs" en el nombre
echo "\n[3] Buscando archivos con 'nodejs' en el nombre:\n";
$searchDirs = ['/var/cpanel', '/home/nexusyl'];
foreach ($searchDirs as $sd) {
    if (is_dir($sd)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sd, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && stripos($file->getFilename(), 'nodejs') !== false) {
                $fp = $file->getPathname();
                echo "  [FILE] $fp (" . $file->getSize() . " bytes)\n";
                if ($file->getSize() < 5000) {
                    echo "  ---\n" . file_get_contents($fp) . "\n";
                }
            }
        }
    }
}

// 4. Buscar archivos con "selector" en el nombre
echo "\n[4] Buscando archivos con 'selector' en el nombre:\n";
foreach ($searchDirs as $sd) {
    if (is_dir($sd)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sd, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && stripos($file->getFilename(), 'selector') !== false) {
                $fp = $file->getPathname();
                echo "  [FILE] $fp (" . $file->getSize() . " bytes)\n";
                if ($file->getSize() < 5000) {
                    echo "  ---\n" . file_get_contents($fp) . "\n";
                }
            }
        }
    }
}

echo "\nCOMPLETADO\n";
