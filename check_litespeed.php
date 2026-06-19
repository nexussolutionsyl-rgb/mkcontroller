<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

echo "=== Diagnostico LiteSpeed ===\n\n";

// 1. Buscar archivos de configuracion de LiteSpeed
$searchPaths = [
    '/home/nexusyl/.htpasswds',
    '/home/nexusyl/.cpanel',
    '/home/nexusyl/.cl.selector',
    '/home/nexusyl/public_html',
    $baseDir,
];

echo "[1] Buscando archivos de configuracion:\n";
foreach ($searchPaths as $path) {
    if (is_dir($path)) {
        $files = scandir($path);
        $count = count($files) - 2;
        echo "  $path: $count archivos\n";
        foreach ($files as $f) {
            if ($f[0] === '.' || strpos($f, 'config') !== false || strpos($f, 'conf') !== false) {
                $fp = $path . '/' . $f;
                if (is_file($fp)) {
                    echo "    [FILE] $f (" . filesize($fp) . " bytes)\n";
                } elseif (is_dir($fp) && $f !== '.' && $f !== '..') {
                    echo "    [DIR] $f/\n";
                }
            }
        }
    } else {
        echo "  $path: NO EXISTE\n";
    }
}

// 2. Verificar si hay archivos .htaccess en directorios padres
echo "\n[2] Buscando .htaccess en padres:\n";
$dir = $baseDir;
while ($dir !== '/' && strlen($dir) > 5) {
    $ht = $dir . '/.htaccess';
    if (file_exists($ht)) {
        echo "  [FILE] $ht (" . filesize($ht) . " bytes)\n";
        echo "  --- CONTENIDO ---\n";
        $content = @file_get_contents($ht);
        if ($content !== false) {
            echo $content . "\n";
        } else {
            echo "  (no se pudo leer)\n";
        }
    }
    $dir = dirname($dir);
}

// 3. Verificar si hay archivo de configuracion del dominio en cPanel
echo "\n[3] Buscando config de dominio:\n";
$userdataPath = '/var/cpanel/userdata/nexusyl';
if (is_dir($userdataPath)) {
    $files = scandir($userdataPath);
    foreach ($files as $f) {
        if (strpos($f, 'nexusmk') !== false || $f === 'nexusmk.nexussolutionsyl.com') {
            $fp = $userdataPath . '/' . $f;
            echo "  [FILE] $f (" . filesize($fp) . " bytes)\n";
            $content = @file_get_contents($fp);
            if ($content !== false) {
                $lines = explode("\n", $content);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (strpos($line, 'documentroot') !== false || 
                        strpos($line, 'server') !== false || 
                        strpos($line, 'ScriptAlias') !== false ||
                        strpos($line, 'Alias') !== false ||
                        strpos($line, 'Proxy') !== false ||
                        strpos($line, 'Passenger') !== false ||
                        strpos($line, 'LiteSpeed') !== false ||
                        strpos($line, '403') !== false ||
                        strpos($line, 'deny') !== false ||
                        strpos($line, 'Deny') !== false) {
                        echo "    $line\n";
                    }
                }
            }
        }
    }
} else {
    echo "  $userdataPath: NO ACCESIBLE\n";
}

// 4. Verificar si hay Passenger config en el sistema
echo "\n[4] Buscando Passenger config:\n";
$passengerFiles = [
    '/etc/apache2/conf.d/passenger.conf',
    '/etc/apache2/conf.d/00-passenger.conf',
    '/usr/local/cpanel/conf/passenger.conf',
    '/home/nexusyl/.cpanel/datastore/PassengerApps',
];
foreach ($passengerFiles as $pf) {
    if (file_exists($pf)) {
        echo "  [FILE] $pf (" . filesize($pf) . " bytes)\n";
        if (filesize($pf) < 5000) {
            echo "  ---\n";
            $content = @file_get_contents($pf);
            if ($content !== false) {
                echo $content . "\n";
            }
        }
    } else {
        echo "  [NO] $pf: NO EXISTE\n";
    }
}

// 5. Verificar si hay un vhost especifico para el subdominio
echo "\n[5] Buscando vhost config:\n";
$vhostPaths = [
    '/etc/apache2/vhosts/nexusmk.nexussolutionsyl.com.conf',
    '/etc/apache2/vhosts/nexusmk.nexussolutionsyl.com',
    '/etc/httpd/vhosts/nexusmk.nexussolutionsyl.com.conf',
    '/usr/local/apache/conf/vhosts/nexusmk.nexussolutionsyl.com.conf',
];
foreach ($vhostPaths as $vp) {
    if (file_exists($vp)) {
        echo "  [FILE] $vp (" . filesize($vp) . " bytes)\n";
        if (filesize($vp) < 10000) {
            echo "  ---\n";
            $content = @file_get_contents($vp);
            if ($content !== false) {
                echo $content . "\n";
            }
        }
    } else {
        echo "  [NO] $vp: NO EXISTE\n";
    }
}

// 6. Verificar LiteSpeed config files
echo "\n[6] Buscando LiteSpeed config:\n";
$lsPaths = [
    '/usr/local/lsws',
    '/etc/litespeed',
];
foreach ($lsPaths as $lp) {
    if (is_dir($lp)) {
        echo "  [DIR] $lp/\n";
        $lsFiles = scandir($lp);
        foreach ($lsFiles as $lf) {
            if ($lf !== '.' && $lf !== '..') {
                $lfp = $lp . '/' . $lf;
                if (is_file($lfp)) {
                    echo "    [FILE] $lf (" . filesize($lfp) . " bytes)\n";
                } elseif (is_dir($lfp)) {
                    echo "    [DIR] $lf/\n";
                }
            }
        }
    } else {
        echo "  [NO] $lp: NO EXISTE\n";
    }
}

echo "\nDIAGNOSTICO COMPLETADO\n";
