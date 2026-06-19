<?php
echo "=== Diagnostico LiteSpeed v2 ===\n\n";

// 1. Buscar httpd_config.conf
echo "[1] Buscando httpd_config.conf:\n";
$paths = [
    '/usr/local/lsws/conf/httpd_config.conf',
    '/usr/local/lsws/conf/httpd_config.xml',
];
foreach ($paths as $p) {
    if (file_exists($p)) {
        echo "  ENCONTRADO: $p (" . filesize($p) . " bytes)\n";
        $content = file_get_contents($p);
        $lines = explode("\n", $content);
        foreach ($lines as $i => $line) {
            $lower = strtolower($line);
            if (strpos($lower, 'nexusmk') !== false || 
                strpos($lower, 'passenger') !== false ||
                strpos($lower, 'node') !== false ||
                strpos($lower, 'docroot') !== false ||
                strpos($lower, 'vhroot') !== false ||
                strpos($lower, 'vhname') !== false ||
                strpos($lower, 'script') !== false ||
                strpos($lower, '403') !== false ||
                strpos($lower, 'deny') !== false) {
                echo "  L$i: $line\n";
            }
        }
    } else {
        echo "  NO: $p\n";
    }
}

// 2. Buscar vhosts config
echo "\n[2] Buscando vhosts:\n";
$vhostDir = '/usr/local/lsws/conf/vhosts';
if (is_dir($vhostDir)) {
    $files = scandir($vhostDir);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            $fp = $vhostDir . '/' . $f;
            echo "  $f (" . filesize($fp) . " bytes)\n";
            if (filesize($fp) < 20000) {
                $content = file_get_contents($fp);
                echo "  --- INICIO ---\n";
                echo $content;
                echo "  --- FIN ---\n";
            }
        }
    }
} else {
    echo "  NO EXISTE: $vhostDir\n";
}

// 3. Buscar en /usr/local/lsws/conf/ todos los archivos
echo "\n[3] Explorando /usr/local/lsws/conf/:\n";
$confDir = '/usr/local/lsws/conf';
if (is_dir($confDir)) {
    $items = scandir($confDir);
    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..') {
            $ip = $confDir . '/' . $item;
            if (is_file($ip)) {
                echo "  [FILE] $item (" . filesize($ip) . " bytes)\n";
            } elseif (is_dir($ip)) {
                echo "  [DIR] $item/\n";
                $subs = scandir($ip);
                foreach ($subs as $sub) {
                    if ($sub !== '.' && $sub !== '..') {
                        $sp = $ip . '/' . $sub;
                        if (is_file($sp)) {
                            echo "    [FILE] $sub (" . filesize($sp) . " bytes)\n";
                        }
                    }
                }
            }
        }
    }
}

// 4. Buscar Passenger module
echo "\n[4] Buscando Passenger module:\n";
$modDirs = [
    '/usr/local/lsws/modules',
    '/usr/local/lsws/modules.6.3.5',
];
foreach ($modDirs as $md) {
    if (is_dir($md)) {
        $mods = scandir($md);
        foreach ($mods as $m) {
            if (strpos(strtolower($m), 'passenger') !== false || 
                strpos(strtolower($m), 'node') !== false) {
                echo "  $md/$m\n";
            }
        }
    }
}

echo "\nDIAGNOSTICO COMPLETADO\n";
