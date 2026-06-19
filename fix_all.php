<?php
// Script completo para:
// 1. Crear .htaccess que permita acceso
// 2. Buscar registro de Node.js Selector
// 3. Intentar limpiar el registro

$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$output = "";

$output .= "========================================\n";
$output .= "FIX COMPLETO - MkController v3.0\n";
$output .= "========================================\n\n";

// ============================================
// PASO 1: Crear .htaccess
// ============================================
$output .= "=== PASO 1: Crear .htaccess ===\n\n";

$htaccess = "# MkController v3.0 - LiteSpeed\n";
$htaccess .= "# Permitir acceso al sitio\n";
$htaccess .= "Require all granted\n\n";

$htaccess .= "# PHP handler\n";
$htaccess .= "<FilesMatch \"\\.php$\">\n";
$htaccess .= "    SetHandler application/x-httpd-ea-php74\n";
$htaccess .= "</FilesMatch>\n\n";

$htaccess .= "# Deny access to sensitive files\n";
$htaccess .= "<FilesMatch \"\\.(env|json|lock|md|gitignore)$\">\n";
$htaccess .= "    Require all denied\n";
$htaccess .= "</FilesMatch>\n\n";

$htaccess .= "# Passenger configuration\n";
$htaccess .= "PassengerEnabled On\n";
$htaccess .= "PassengerAppRoot $baseDir\n";
$htaccess .= "PassengerAppType node\n";
$htaccess .= "PassengerStartupFile passenger.js\n";
$htaccess .= "PassengerNodePath /opt/alt/alt-nodejs20/root/usr/bin/node\n";
$htaccess .= "PassengerFriendlyErrorPages On\n";
$htaccess .= "PassengerEnv NODE_ENV production\n";
$htaccess .= "PassengerEnv PORT 3000\n\n";

$htaccess .= "# SPA - servir frontend/index.html para rutas no encontradas\n";
$htaccess .= "<IfModule mod_rewrite.c>\n";
$htaccess .= "    RewriteEngine On\n";
$htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
$htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-d\n";
$htaccess .= "    RewriteRule ^api/(.*)$ backend/app.js [L]\n";
$htaccess .= "</IfModule>\n";

file_put_contents($baseDir . '/.htaccess', $htaccess);
$output .= "✅ .htaccess creado en $baseDir/.htaccess\n";
$output .= "Contenido:\n$htaccess\n\n";

// ============================================
// PASO 2: Buscar registro de Node.js Selector
// ============================================
$output .= "=== PASO 2: Buscar registro de Node.js Selector ===\n\n";

// Buscar en /var/cpanel/
$dirsToCheck = [
    '/var/cpanel',
    '/etc/cpanel',
    '/usr/local/cpanel',
    '/home/nexusyl/.cpanel',
];

foreach ($dirsToCheck as $dir) {
    if (!is_dir($dir)) {
        $output .= "❌ $dir (no existe o no accesible)\n";
        continue;
    }
    $output .= "📁 Escaneando $dir:\n";
    $files = scandir($dir);
    foreach ($files as $f) {
        if ($f == '.' || $f == '..') continue;
        $path = $dir . '/' . $f;
        if (is_file($path)) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            $name = strtolower($f);
            if (in_array($ext, ['db', 'sqlite', 'sqlite3', 'dat']) || 
                strpos($name, 'node') !== false || 
                strpos($name, 'passenger') !== false ||
                strpos($name, 'selector') !== false ||
                strpos($name, 'app') !== false) {
                $size = filesize($path);
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $output .= "   📄 $f ($size bytes, permisos: $perms)\n";
                if ($size < 50000 && $size > 0) {
                    $content = file_get_contents($path);
                    if (strpos($content, 'nexusmk') !== false || strpos($content, $baseDir) !== false) {
                        $output .= "   ⚠️ CONTIENE REFERENCIA A NEXUSMK:\n";
                        $output .= "   " . str_replace("\n", "\n   ", $content) . "\n\n";
                    }
                }
            }
        }
    }
    $output .= "\n";
}

// ============================================
// PASO 3: Buscar en /var/cpanel/userdata/
// ============================================
$output .= "=== PASO 3: Buscar en /var/cpanel/userdata/ ===\n\n";

$userdataDir = '/var/cpanel/userdata';
if (is_dir($userdataDir)) {
    $items = scandir($userdataDir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        $path = $userdataDir . '/' . $item;
        if (is_file($path) && strpos($item, 'nexusmk') !== false) {
            $output .= "📄 $item:\n";
            $output .= file_get_contents($path) . "\n\n";
        }
        if (is_dir($path) && $item == 'nexusyl') {
            $output .= "📁 userdata/nexusyl:\n";
            $files = scandir($path);
            foreach ($files as $f) {
                if ($f == '.' || $f == '..') continue;
                $fp = $path . '/' . $f;
                if (is_file($fp)) {
                    $content = file_get_contents($fp);
                    if (strpos($content, 'node') !== false || strpos($content, 'passenger') !== false) {
                        $output .= "   📄 $f (contiene referencias a node/passenger)\n";
                        $lines = explode("\n", $content);
                        foreach ($lines as $line) {
                            if (strpos($line, 'node') !== false || strpos($line, 'passenger') !== false) {
                                $output .= "     > $line\n";
                            }
                        }
                    }
                }
            }
        }
    }
} else {
    $output .= "❌ $userdataDir (no existe o no accesible)\n";
}

$output .= "\n";

// ============================================
// PASO 4: Intentar ejecutar comandos de cPanel
// ============================================
$output .= "=== PASO 4: Ejecutar comandos de diagnóstico ===\n\n";

$commands = [
    '/usr/local/cpanel/bin/whmapi1 --output=json list_nodejs_applications 2>&1',
    '/usr/local/cpanel/bin/uapi --user=nexusyl NodeJS list_applications 2>&1',
    '/usr/local/cpanel/bin/uapi --user=nexusyl NodeApp list_applications 2>&1',
    '/usr/local/cpanel/bin/uapi --user=nexusyl NodeSelector list_applications 2>&1',
    'ls -la /var/cpanel/ 2>&1',
    'find /var/cpanel/ -name "*.db" -o -name "*.sqlite" 2>&1',
];

foreach ($commands as $cmd) {
    $output .= "Ejecutando: $cmd\n";
    exec($cmd, $cmdOut, $cmdCode);
    $output .= "Código: $cmdCode\n";
    $output .= "Salida: " . implode("\n", $cmdOut) . "\n\n";
}

// ============================================
// PASO 5: Verificar estado actual
// ============================================
$output .= "=== PASO 5: Verificar estado actual ===\n\n";

$checks = [
    'passenger.js' => file_exists($baseDir . '/passenger.js'),
    'start.js' => file_exists($baseDir . '/start.js'),
    'package.json' => file_exists($baseDir . '/package.json'),
    'node' => file_exists($baseDir . '/node'),
    'npm' => file_exists($baseDir . '/npm'),
    'node_modules' => is_dir($baseDir . '/node_modules'),
    '.env (root)' => file_exists($baseDir . '/.env'),
    '.env (backend)' => file_exists($baseDir . '/backend/.env'),
    '.htaccess' => file_exists($baseDir . '/.htaccess'),
];

foreach ($checks as $name => $exists) {
    $output .= ($exists ? "✅" : "❌") . " $name\n";
}

$output .= "\n========================================\n";
$output .= "SCRIPT COMPLETADO\n";
$output .= "========================================\n";

echo $output;
