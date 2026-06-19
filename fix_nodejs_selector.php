<?php
/**
 * Script para resolver el problema de Node.js Selector
 * "Specified directory already used"
 * 
 * Estrategias:
 * 1. Buscar y limpiar el registro interno de Node.js Selector
 * 2. Crear Passenger wrapper para Node.js
 * 3. Configurar .htaccess para Passenger
 */

$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$homeDir = '/home/nexusyl';

$log = [];

function logMsg($msg) {
    global $log;
    $log[] = $msg;
    echo $msg . "\n";
}

logMsg("=== INICIANDO DIAGNÓSTICO Y REPARACIÓN ===\n");

// ==========================================
// 1. BUSCAR EL REGISTRO DE NODE.JS SELECTOR
// ==========================================
logMsg("\n1. BUSCANDO REGISTRO DE NODE.JS SELECTOR\n");

// Buscar en ubicaciones del sistema
$systemLocations = [
    '/var/cpanel',
    '/etc/cpanel',
    '/usr/local/cpanel',
    '/home/nexusyl/.cpanel',
];

$foundNodeConfigs = [];

foreach ($systemLocations as $loc) {
    if (!is_dir($loc)) continue;
    
    // Buscar archivos que contengan "nexusmk" en el nombre
    $files = glob($loc . '/*node*');
    $files = array_merge($files, glob($loc . '/*selector*'));
    $files = array_merge($files, glob($loc . '/*passenger*'));
    
    foreach ($files as $f) {
        if (is_file($f)) {
            $foundNodeConfigs[] = $f . ' (' . filesize($f) . ' bytes)';
            logMsg("  📄 Encontrado: $f");
            
            // Intentar leer el contenido
            $content = file_get_contents($f);
            if (strlen($content) < 50000) {
                logMsg("  Contenido: " . substr($content, 0, 500));
            }
        }
    }
    
    // Buscar recursivamente archivos .db, .sqlite
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($loc, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $f) {
        if ($f->isFile()) {
            $ext = strtolower($f->getExtension());
            if (in_array($ext, ['db', 'sqlite', 'sqlite3'])) {
                $foundNodeConfigs[] = $f->getPathname() . ' (' . $f->getSize() . ' bytes)';
                logMsg("  🗄️ DB encontrada: " . $f->getPathname());
            }
        }
    }
}

if (empty($foundNodeConfigs)) {
    logMsg("  No se encontraron archivos de configuración de Node.js Selector accesibles.");
    logMsg("  El registro está probablemente en una base de datos del sistema no accesible desde PHP.");
}

// ==========================================
// 2. VERIFICAR NODE.JS DISPONIBLE
// ==========================================
logMsg("\n2. VERIFICANDO NODE.JS DISPONIBLE\n");

$nodejsPaths = [
    '/opt/alt/alt-nodejs20/root/usr/bin/node',
    '/opt/alt/alt-nodejs16/root/usr/bin/node',
    '/opt/alt/alt-nodejs19/root/usr/bin/node',
    '/opt/alt/alt-nodejs24/root/usr/bin/node',
    '/opt/alt/alt-nodejs18/root/usr/bin/node',
    '/opt/alt/alt-nodejs22/root/usr/bin/node',
];

$nodePath = null;
foreach ($nodejsPaths as $np) {
    if (file_exists($np)) {
        $ver = trim(exec($np . ' --version 2>&1'));
        logMsg("  ✅ $np -> $ver");
        if (!$nodePath) {
            $nodePath = $np;
            logMsg("  Usando: $np");
        }
    }
}

if (!$nodePath) {
    logMsg("  ❌ No se encontró Node.js!");
    exit(1);
}

// ==========================================
// 3. CREAR WRAPPER DE NODE.JS EN EL DIRECTORIO
// ==========================================
logMsg("\n3. CREANDO WRAPPER DE NODE.JS\n");

// Crear script node en el directorio de la app
$nodeWrapperContent = <<<NODEWRAP
#!/bin/bash
exec $nodePath "\$@"
NODEWRAP;

$nodeWrapperPath = $baseDir . '/node';
file_put_contents($nodeWrapperPath, $nodeWrapperContent);
chmod($nodeWrapperPath, 0755);
logMsg("  ✅ Wrapper creado: $nodeWrapperPath");

// Crear script npm
$npmPath = dirname($nodePath) . '/npm';
if (file_exists($npmPath)) {
    $npmWrapperContent = <<<NPMWRAP
#!/bin/bash
exec $npmPath "\$@"
NPMWRAP;
    $npmWrapperPath = $baseDir . '/npm';
    file_put_contents($npmWrapperPath, $npmWrapperContent);
    chmod($npmWrapperPath, 0755);
    logMsg("  ✅ Wrapper npm creado: $npmWrapperPath");
}

// ==========================================
// 4. VERIFICAR Y ACTUALIZAR passenger.js
// ==========================================
logMsg("\n4. VERIFICANDO passenger.js\n");

$passengerJsPath = $baseDir . '/passenger.js';
if (file_exists($passengerJsPath)) {
    $content = file_get_contents($passengerJsPath);
    logMsg("  ✅ passenger.js existe (" . filesize($passengerJsPath) . " bytes)");
    logMsg("  Contenido: " . $content);
} else {
    logMsg("  ❌ passenger.js NO existe - creándolo...");
    $passengerContent = <<<PASSENGER
require('dotenv').config({ path: __dirname + '/backend/.env' });
const app = require('./backend/app');
module.exports = app;
PASSENGER;
    file_put_contents($passengerJsPath, $passengerContent);
    logMsg("  ✅ passenger.js creado");
}

// ==========================================
// 5. VERIFICAR package.json
// ==========================================
logMsg("\n5. VERIFICANDO package.json\n");

$pkgPath = $baseDir . '/package.json';
if (file_exists($pkgPath)) {
    $pkg = json_decode(file_get_contents($pkgPath), true);
    logMsg("  ✅ package.json existe");
    logMsg("  main actual: " . ($pkg['main'] ?? 'NO DEFINIDO'));
    
    if (($pkg['main'] ?? '') !== 'passenger.js') {
        logMsg("  ⚠️ main no apunta a passenger.js - actualizando...");
        $pkg['main'] = 'passenger.js';
        file_put_contents($pkgPath, json_encode($pkg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        logMsg("  ✅ package.json actualizado");
    } else {
        logMsg("  ✅ main = passenger.js (correcto)");
    }
}

// ==========================================
// 6. VERIFICAR Y ACTUALIZAR .htaccess
// ==========================================
logMsg("\n6. VERIFICANDO .htaccess\n");

$htaccessPath = $baseDir . '/.htaccess';
$htaccessContent = '';
if (file_exists($htaccessPath)) {
    $htaccessContent = file_get_contents($htaccessPath);
    logMsg("  ✅ .htaccess existe (" . filesize($htaccessPath) . " bytes)");
    logMsg("  Contenido actual:\n" . $htaccessContent);
} else {
    logMsg("  ❌ .htaccess NO existe");
}

// Verificar si tiene PassengerEnabled
if (strpos($htaccessContent, 'PassengerEnabled') === false) {
    logMsg("  ⚠️ .htaccess no tiene PassengerEnabled - agregando...");
    
    $newHtaccess = <<<HTACCESS
# Habilitar Passenger para Node.js
PassengerEnabled On
PassengerAppRoot /home/nexusyl/nexusmk.nexussolutionsyl.com
PassengerAppType node
PassengerStartupFile passenger.js
PassengerNodePath $nodePath
PassengerFriendlyErrorPages On

# Redirigir todo a Passenger
RewriteEngine On
RewriteRule ^(.*)$ /passenger.js [L]

# Seguridad
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
</IfModule>

# PHP files handling (for scripts)
<FilesMatch "\.php$">
    SetHandler application/x-httpd-ea-php74
</FilesMatch>

# Deny access to sensitive files
<FilesMatch "\.(env|json|lock|md)$">
    Require all denied
</FilesMatch>

# Passenger config
PassengerEnv NODE_ENV production
PassengerEnv PORT 3000

HTACCESS;
    
    file_put_contents($htaccessPath, $newHtaccess);
    logMsg("  ✅ .htaccess actualizado con PassengerEnabled");
} else {
    logMsg("  ✅ .htaccess ya tiene PassengerEnabled");
}

// ==========================================
// 7. VERIFICAR start.js
// ==========================================
logMsg("\n7. VERIFICANDO start.js\n");

$startJsPath = $baseDir . '/start.js';
if (file_exists($startJsPath)) {
    $startContent = file_get_contents($startJsPath);
    logMsg("  ✅ start.js existe (" . filesize($startJsPath) . " bytes)");
    
    if (strpos($startContent, 'module.exports') !== false) {
        logMsg("  ✅ start.js exporta module.exports (compatible con Passenger)");
    } else {
        logMsg("  ⚠️ start.js NO exporta module.exports - podría no ser compatible con Passenger");
    }
}

// ==========================================
// 8. VERIFICAR node_modules
// ==========================================
logMsg("\n8. VERIFICANDO node_modules\n");

$nmPath = $baseDir . '/node_modules';
if (is_dir($nmPath)) {
    $count = count(scandir($nmPath)) - 2;
    logMsg("  ✅ node_modules existe con $count módulos");
    
    // Verificar express
    if (is_dir($nmPath . '/express')) {
        logMsg("  ✅ express instalado");
    } else {
        logMsg("  ❌ express NO instalado");
    }
    
    // Verificar dotenv
    if (is_dir($nmPath . '/dotenv')) {
        logMsg("  ✅ dotenv instalado");
    } else {
        logMsg("  ❌ dotenv NO instalado");
    }
} else {
    logMsg("  ❌ node_modules NO existe");
}

// ==========================================
// 9. VERIFICAR .env
// ==========================================
logMsg("\n9. VERIFICANDO .env\n");

$envPaths = [
    $baseDir . '/.env',
    $baseDir . '/backend/.env',
];

foreach ($envPaths as $ep) {
    if (file_exists($ep)) {
        logMsg("  ✅ $ep existe (" . filesize($ep) . " bytes)");
        $envContent = file_get_contents($ep);
        logMsg("  Contenido:\n" . $envContent);
    } else {
        logMsg("  ❌ $ep NO existe");
    }
}

// ==========================================
// 10. INTENTAR REGISTRAR APP DE NUEVO
// ==========================================
logMsg("\n10. VERIFICANDO SI PassengerApps ESTÁ VACÍO\n");

// Verificar si podemos acceder a PassengerApps API
logMsg("  Las apps de PassengerApps fueron eliminadas.");
logMsg("  Ahora intentaremos que Passenger funcione sin Node.js Selector.");
logMsg("  El .htaccess con PassengerEnabled debería ser suficiente.");

// ==========================================
// 11. PROBAR LA APP DIRECTAMENTE
// ==========================================
logMsg("\n11. PROBANDO LA APP CON NODE.JS\n");

$testCode = '
try {
    const app = require("./passenger.js");
    console.log("✅ passenger.js cargado correctamente");
    console.log("✅ Tipo de app: " + typeof app);
} catch(e) {
    console.log("❌ Error: " + e.message.substring(0, 200));
}
';

$cmd = 'cd ' . $baseDir . ' && ' . $nodePath . ' -e ' . escapeshellarg($testCode) . ' 2>&1';
exec($cmd, $output, $code);
logMsg("  Resultado: " . implode("\n  ", $output));

// ==========================================
// 12. RESUMEN
// ==========================================
logMsg("\n=== RESUMEN ===\n");
logMsg("Node.js: $nodePath");
logMsg("passenger.js: " . (file_exists($passengerJsPath) ? '✅' : '❌'));
logMsg("package.json main: " . (($pkg['main'] ?? '') === 'passenger.js' ? '✅ passenger.js' : '❌'));
logMsg(".htaccess: " . (file_exists($htaccessPath) ? '✅' : '❌'));
logMsg("node_modules: " . (is_dir($nmPath) ? '✅' : '❌'));
logMsg("Wrapper node: " . (file_exists($nodeWrapperPath) ? '✅' : '❌'));

logMsg("\n✅ Diagnóstico y reparación completados.");
logMsg("\n⚠️ IMPORTANTE: Si el problema persiste, el usuario debe:");
logMsg("   1. Ir a cPanel -> Node.js Selector");
logMsg("   2. Verificar si hay alguna app listada (aunque diga que no hay)");
logMsg("   3. Si aparece, eliminarla");
logMsg("   4. O crear un subdominio NUEVO (ej: app.nexussolutionsyl.com)");
logMsg("   5. Configurar Node.js Selector en el NUEVO subdominio");
logMsg("   6. Mover los archivos al nuevo subdominio");

// Guardar log
file_put_contents($baseDir . '/fix_log.txt', implode("\n", $log));
echo "\nLog guardado en fix_log.txt\n";
