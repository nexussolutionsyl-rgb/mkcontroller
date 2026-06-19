<?php
echo "<pre>\n";
echo "=== ANÁLISIS COMPLETO DE NODE.JS SELECTOR ===\n\n";

$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$homeDir = '/home/nexusyl';

echo "1. VERIFICANDO ARCHIVOS DE CONFIGURACIÓN DE PASSENGER\n";
echo "====================================================\n\n";

// Passenger config files
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
        echo "✅ $f\n";
        echo "   " . str_replace("\n", "\n   ", file_get_contents($f)) . "\n\n";
    } else {
        echo "❌ $f (no existe)\n";
    }
}

echo "\n2. VERIFICANDO ARCHIVOS DE NODE.JS SELECTOR\n";
echo "============================================\n\n";

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
        echo "✅ $f (" . filesize($f) . " bytes)\n";
        if (filesize($f) < 100000) {
            echo "   " . str_replace("\n", "\n   ", file_get_contents($f)) . "\n\n";
        }
    } else {
        echo "❌ $f (no existe)\n";
    }
}

echo "\n3. BUSCANDO ARCHIVOS SQLITE/DATABASE EN /home/nexusyl/\n";
echo "=======================================================\n\n";

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
    echo "No se encontraron archivos de base de datos relacionados.\n";
} else {
    foreach ($foundFiles as $ff) {
        echo "📁 $ff\n";
    }
}

echo "\n4. BUSCANDO EN /var/cpanel/ Y /etc/cpanel/\n";
echo "============================================\n\n";

$systemDirs = ['/var/cpanel', '/etc/cpanel', '/usr/local/cpanel'];
foreach ($systemDirs as $dir) {
    if (!is_dir($dir)) {
        echo "❌ $dir (no existe o no accesible)\n";
        continue;
    }
    echo "📁 $dir:\n";
    $files = scandir($dir);
    $found = false;
    foreach ($files as $f) {
        if ($f == '.' || $f == '..') continue;
        $path = $dir . '/' . $f;
        if (is_file($path) && (strpos(strtolower($f), 'node') !== false || 
            strpos(strtolower($f), 'passenger') !== false ||
            strpos(strtolower($f), 'selector') !== false)) {
            echo "   ✅ $f\n";
            $found = true;
        }
    }
    if (!$found) {
        echo "   (sin archivos relacionados)\n";
    }
}

echo "\n5. VERIFICANDO .htaccess ACTUAL\n";
echo "================================\n\n";

$htaccessPath = $baseDir . '/.htaccess';
if (file_exists($htaccessPath)) {
    echo "✅ .htaccess existe:\n";
    echo file_get_contents($htaccessPath) . "\n";
} else {
    echo "❌ .htaccess NO existe\n";
}

echo "\n6. VERIFICANDO PassengerApp REGISTRADA\n";
echo "========================================\n\n";

// Check if there's a Passenger config file in the app directory
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
        echo "✅ $f\n";
        echo "   " . str_replace("\n", "\n   ", file_get_contents($f)) . "\n\n";
    }
}

echo "\n7. VERIFICANDO passenger.js Y start.js\n";
echo "========================================\n\n";

$filesToCheck = [
    $baseDir . '/passenger.js',
    $baseDir . '/start.js',
    $baseDir . '/package.json',
    $baseDir . '/backend/app.js',
    $baseDir . '/backend/package.json',
];

foreach ($filesToCheck as $f) {
    if (file_exists($f)) {
        echo "✅ $f (" . filesize($f) . " bytes)\n";
        echo "   --- PRIMERAS 5 LÍNEAS ---\n";
        $lines = file($f);
        for ($i = 0; $i < min(5, count($lines)); $i++) {
            echo "   " . rtrim($lines[$i]) . "\n";
        }
        echo "\n";
    } else {
        echo "❌ $f (no existe)\n";
    }
}

echo "\n8. VERIFICANDO NODE BINARIO DISPONIBLE\n";
echo "========================================\n\n";

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
        echo "✅ $np -> $ver\n";
    }
}

echo "\n9. VERIFICANDO SI HAY ARCHIVOS OCULTOS DE NODE.JS SELECTOR\n";
echo "============================================================\n\n";

// Check for hidden files/dirs that might contain Node.js Selector config
$hiddenItems = scandir($baseDir);
foreach ($hiddenItems as $item) {
    if ($item[0] === '.' && $item !== '.' && $item !== '..') {
        $path = $baseDir . '/' . $item;
        if (is_dir($path)) {
            echo "📁 Directorio oculto: $item/\n";
        } elseif (is_file($path)) {
            echo "📄 Archivo oculto: $item (" . filesize($path) . " bytes)\n";
            if (filesize($path) < 50000) {
                echo "   " . str_replace("\n", "\n   ", file_get_contents($path)) . "\n\n";
            }
        }
    }
}

echo "\n10. VERIFICANDO PERMISOS DEL DIRECTORIO\n";
echo "==========================================\n\n";

echo "Directorio: $baseDir\n";
echo "Propietario: " . posix_getpwuid(fileowner($baseDir))['name'] . "\n";
echo "Grupo: " . posix_getgrgid(filegroup($baseDir))['name'] . "\n";
echo "Permisos: " . substr(sprintf('%o', fileperms($baseDir)), -4) . "\n";

echo "\n11. VERIFICANDO SI HAY ARCHIVOS DE REGISTRO EN /var/cpanel/userdata/\n";
echo "====================================================================\n\n";

$userdataDir = '/var/cpanel/userdata/nexusyl';
if (is_dir($userdataDir)) {
    echo "📁 $userdataDir:\n";
    $files = scandir($userdataDir);
    foreach ($files as $f) {
        if ($f == '.' || $f == '..') continue;
        $path = $userdataDir . '/' . $f;
        if (is_file($path)) {
            $size = filesize($path);
            echo "   $f ($size bytes)\n";
            if ($size < 50000 && (strpos($f, 'nexusmk') !== false || strpos($f, 'node') !== false)) {
                echo "   " . str_replace("\n", "\n   ", file_get_contents($path)) . "\n\n";
            }
        }
    }
} else {
    echo "❌ $userdataDir (no existe)\n";
}

echo "\n=== ANÁLISIS COMPLETADO ===\n";
echo "</pre>\n";
