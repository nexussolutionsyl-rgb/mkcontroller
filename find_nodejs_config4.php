<?php
$output = "";
$output .= "=== ANÁLISIS COMPLETO DE NODE.JS SELECTOR ===\n\n";

$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$homeDir = '/home/nexusyl';

$output .= "1. VERIFICANDO ARCHIVOS DE CONFIGURACIÓN DE PASSENGER\n";
$output .= "====================================================\n\n";

$passengerFiles = [
    '/etc/cpanel/passenger.conf',
    '/etc/cpanel/ea4/passenger.conf',
    '/etc/cpanel/ea-ruby27/passenger.conf',
    '/usr/local/cpanel/passenger.conf',
    '/home/nexusyl/.cpanel/passenger.conf',
    '/home/nexusyl/.cpanel/passenger.json',
];

foreach ($passengerFiles as $f) {
    if (file_exists($f)) {
        $output .= "✅ $f\n";
        $output .= "   " . str_replace("\n", "\n   ", file_get_contents($f)) . "\n\n";
    } else {
        $output .= "❌ $f (no existe)\n";
    }
}

$output .= "\n2. VERIFICANDO ARCHIVOS DE NODE.JS SELECTOR\n";
$output .= "============================================\n\n";

$nodeSelectorFiles = [
    '/home/nexusyl/.nodejs/config.json',
    '/home/nexusyl/.nodejs/versions.json',
    '/home/nexusyl/.cpanel/nodejs.conf',
    '/home/nexusyl/.cpanel/nodejs.json',
    '/home/nexusyl/.cpanel/nodejs.db',
    '/home/nexusyl/.cpanel/nodejs.sqlite',
    '/var/cpanel/nodejs.db',
    '/var/cpanel/nodejs.sqlite',
    '/etc/cpanel/nodejs.conf',
    '/etc/cpanel/nodejs.json',
    '/etc/cpanel/nodejs.db',
    '/usr/local/cpanel/nodejs.db',
    '/usr/local/cpanel/nodejs.sqlite',
];

foreach ($nodeSelectorFiles as $f) {
    if (file_exists($f)) {
        $output .= "✅ $f (" . filesize($f) . " bytes)\n";
        if (filesize($f) < 100000) {
            $output .= "   " . str_replace("\n", "\n   ", file_get_contents($f)) . "\n\n";
        }
    } else {
        $output .= "❌ $f (no existe)\n";
    }
}

$output .= "\n3. BUSCANDO ARCHIVOS SQLITE/DATABASE EN /home/nexusyl/\n";
$output .= "=======================================================\n\n";

$searchDirs = [
    $homeDir . '/.cpanel',
    $homeDir . '/.cl.selector',
    $homeDir,
];

$foundFiles = [];
foreach ($searchDirs as $dir) {
    if (!is_dir($dir)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $f) {
        if ($f->isFile()) {
            $ext = strtolower($f->getExtension());
            $name = strtolower($f->getFilename());
            if (in_array($ext, ['db', 'sqlite', 'sqlite3', 'dat']) || 
                strpos($name, 'node') !== false || 
                strpos($name, 'passenger') !== false ||
                strpos($name, 'selector') !== false) {
                $foundFiles[] = $f->getPathname() . " (" . $f->getSize() . " bytes)";
            }
        }
    }
}

if (empty($foundFiles)) {
    $output .= "No se encontraron archivos de base de datos relacionados.\n";
} else {
    foreach ($foundFiles as $ff) {
        $output .= "📁 $ff\n";
    }
}

$output .= "\n4. BUSCANDO EN /var/cpanel/ Y /etc/cpanel/\n";
$output .= "============================================\n\n";

$systemDirs = ['/var/cpanel', '/etc/cpanel', '/usr/local/cpanel'];
foreach ($systemDirs as $dir) {
    if (!is_dir($dir)) {
        $output .= "❌ $dir (no existe o no accesible)\n";
        continue;
    }
    $output .= "📁 $dir:\n";
    $files = scandir($dir);
    $found = false;
    foreach ($files as $f) {
        if ($f == '.' || $f == '..') continue;
        $path = $dir . '/' . $f;
        if (is_file($path) && (strpos(strtolower($f), 'node') !== false || 
            strpos(strtolower($f), 'passenger') !== false ||
            strpos(strtolower($f), 'selector') !== false)) {
            $output .= "   ✅ $f\n";
            $found = true;
        }
    }
    if (!$found) {
        $output .= "   (sin archivos relacionados)\n";
    }
}

$output .= "\n5. VERIFICANDO .htaccess ACTUAL\n";
$output .= "================================\n\n";

$htaccessPath = $baseDir . '/.htaccess';
if (file_exists($htaccessPath)) {
    $output .= "✅ .htaccess existe:\n";
    $output .= file_get_contents($htaccessPath) . "\n";
} else {
    $output .= "❌ .htaccess NO existe\n";
}

$output .= "\n6. VERIFICANDO PassengerApp REGISTRADA\n";
$output .= "========================================\n\n";

$passengerConfigs = [
    $baseDir . '/passenger_wsgi.py',
    $baseDir . '/passenger_wsgi.js',
    $baseDir . '/.passenger.json',
    $baseDir . '/.passenger.conf',
    $baseDir . '/config.ru',
    $baseDir . '/Gemfile',
];

foreach ($passengerConfigs as $f) {
    if (file_exists($f)) {
        $output .= "✅ $f\n";
        $output .= "   " . str_replace("\n", "\n   ", file_get_contents($f)) . "\n\n";
    }
}

$output .= "\n7. VERIFICANDO passenger.js Y start.js\n";
$output .= "========================================\n\n";

$filesToCheck = [
    $baseDir . '/passenger.js',
    $baseDir . '/start.js',
    $baseDir . '/package.json',
    $baseDir . '/backend/app.js',
    $baseDir . '/backend/package.json',
];

foreach ($filesToCheck as $f) {
    if (file_exists($f)) {
        $output .= "✅ $f (" . filesize($f) . " bytes)\n";
        $output .= "   --- PRIMERAS 5 LÍNEAS ---\n";
        $lines = file($f);
        for ($i = 0; $i < min(5, count($lines)); $i++) {
            $output .= "   " . rtrim($lines[$i]) . "\n";
        }
        $output .= "\n";
    } else {
        $output .= "❌ $f (no existe)\n";
    }
}

$output .= "\n8. VERIFICANDO NODE BINARIO DISPONIBLE\n";
$output .= "========================================\n\n";

$nodePaths = [
    '/opt/alt/alt-nodejs20/root/usr/bin/node',
    '/opt/alt/alt-nodejs16/root/usr/bin/node',
    '/opt/alt/alt-nodejs19/root/usr/bin/node',
    '/opt/alt/alt-nodejs24/root/usr/bin/node',
    '/opt/alt/alt-nodejs18/root/usr/bin/node',
    '/opt/alt/alt-nodejs22/root/usr/bin/node',
    '/opt/cpanel/ea-ruby27/root/usr/share/passenger/node/bin/node',
    '/usr/local/bin/node',
    '/usr/bin/node',
];

foreach ($nodePaths as $np) {
    if (file_exists($np)) {
        $ver = exec($np . ' --version 2>&1');
        $output .= "✅ $np -> $ver\n";
    }
}

$output .= "\n9. VERIFICANDO SI HAY ARCHIVOS OCULTOS DE NODE.JS SELECTOR\n";
$output .= "============================================================\n\n";

$hiddenItems = scandir($baseDir);
foreach ($hiddenItems as $item) {
    if ($item[0] === '.' && $item !== '.' && $item !== '..') {
        $path = $baseDir . '/' . $item;
        if (is_dir($path)) {
            $output .= "📁 Directorio oculto: $item/\n";
        } elseif (is_file($path)) {
            $output .= "📄 Archivo oculto: $item (" . filesize($path) . " bytes)\n";
            if (filesize($path) < 50000) {
                $output .= "   " . str_replace("\n", "\n   ", file_get_contents($path)) . "\n\n";
            }
        }
    }
}

$output .= "\n10. VERIFICANDO PERMISOS DEL DIRECTORIO\n";
$output .= "==========================================\n\n";

$output .= "Directorio: $baseDir\n";
$output .= "Propietario: " . posix_getpwuid(fileowner($baseDir))['name'] . "\n";
$output .= "Grupo: " . posix_getgrgid(filegroup($baseDir))['name'] . "\n";
$output .= "Permisos: " . substr(sprintf('%o', fileperms($baseDir)), -4) . "\n";

$output .= "\n11. VERIFICANDO SI HAY ARCHIVOS DE REGISTRO EN /var/cpanel/userdata/\n";
$output .= "====================================================================\n\n";

$userdataDir = '/var/cpanel/userdata/nexusyl';
if (is_dir($userdataDir)) {
    $output .= "📁 $userdataDir:\n";
    $files = scandir($userdataDir);
    foreach ($files as $f) {
        if ($f == '.' || $f == '..') continue;
        $path = $userdataDir . '/' . $f;
        if (is_file($path)) {
            $size = filesize($path);
            $output .= "   $f ($size bytes)\n";
            if ($size < 50000 && (strpos($f, 'nexusmk') !== false || strpos($f, 'node') !== false)) {
                $output .= "   " . str_replace("\n", "\n   ", file_get_contents($path)) . "\n\n";
            }
        }
    }
} else {
    $output .= "❌ $userdataDir (no existe)\n";
}

$output .= "\n12. BUSCANDO EN /home/nexusyl/ TODOS LOS ARCHIVOS .json Y .conf\n";
$output .= "================================================================\n\n";

$searchPatterns = ['*.json', '*.conf'];
foreach ($searchPatterns as $pattern) {
    $files = glob($homeDir . '/{' . $pattern . '}', GLOB_BRACE);
    foreach ($files as $f) {
        if (is_file($f) && filesize($f) < 50000) {
            $content = file_get_contents($f);
            if (strpos($content, 'nexusmk') !== false || strpos($content, 'node') !== false || strpos($content, 'passenger') !== false) {
                $output .= "📄 $f (" . filesize($f) . " bytes)\n";
                $output .= "   " . str_replace("\n", "\n   ", $content) . "\n\n";
            }
        }
    }
}

$output .= "\n=== ANÁLISIS COMPLETADO ===\n";

// Guardar a archivo
file_put_contents($baseDir . '/diagnostico_nodejs.txt', $output);
echo "Diagnóstico guardado en diagnostico_nodejs.txt\n";
echo "Tamaño del output: " . strlen($output) . " bytes\n";
