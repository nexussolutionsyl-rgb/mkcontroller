<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

echo "=== MkController - Fix .htaccess v2 ===\n\n";

// 1. Eliminar .htaccess actual
$htaccessPath = $baseDir . '/.htaccess';
if (file_exists($htaccessPath)) {
    unlink($htaccessPath);
    echo "✅ .htaccess ELIMINADO\n";
} else {
    echo "⚠️ .htaccess no existe\n";
}

// 2. Crear nuevo .htaccess compatible con LiteSpeed
$htaccess = "# MkController v3.0 - LiteSpeed\n";
$htaccess .= "# Permitir acceso\n";
$htaccess .= "Satisfy any\n";
$htaccess .= "Order allow,deny\n";
$htaccess .= "Allow from all\n";
$htaccess .= "Require all granted\n\n";

$htaccess .= "# PHP handler\n";
$htaccess .= "<FilesMatch \"\\.php$\">\n";
$htaccess .= "    SetHandler application/x-httpd-ea-php74\n";
$htaccess .= "</FilesMatch>\n\n";

$htaccess .= "# Deny access to sensitive files\n";
$htaccess .= "<FilesMatch \"\\.(env|json|lock|md|gitignore|ps1|txt)$\">\n";
$htaccess .= "    Require all denied\n";
$htaccess .= "</FilesMatch>\n\n";

$htaccess .= "# SPA - servir frontend/index.html para rutas no encontradas\n";
$htaccess .= "<IfModule mod_rewrite.c>\n";
$htaccess .= "    RewriteEngine On\n";
$htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
$htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-d\n";
$htaccess .= "    RewriteRule ^(.*)$ frontend/index.html [L]\n";
$htaccess .= "</IfModule>\n";

$htaccess .= "\n# Seguridad adicional\n";
$htaccess .= "Options -Indexes\n";
$htaccess .= "ServerSignature Off\n";

file_put_contents($htaccessPath, $htaccess);
echo "✅ Nuevo .htaccess CREADO\n";
echo "Tamaño: " . filesize($htaccessPath) . " bytes\n";
echo "--- CONTENIDO ---\n";
echo file_get_contents($htaccessPath) . "\n";

// 3. Verificar que passenger.js existe y es correcto
echo "--- VERIFICACIÓN passenger.js ---\n";
$passengerPath = $baseDir . '/passenger.js';
if (file_exists($passengerPath)) {
    echo "✅ passenger.js existe\n";
    echo "Contenido:\n" . file_get_contents($passengerPath) . "\n";
} else {
    echo "❌ passenger.js NO existe\n";
}

// 4. Verificar package.json
echo "--- VERIFICACIÓN package.json ---\n";
$pkgPath = $baseDir . '/package.json';
if (file_exists($pkgPath)) {
    $pkg = json_decode(file_get_contents($pkgPath), true);
    echo "✅ package.json existe\n";
    echo "main: " . ($pkg['main'] ?? 'NO DEFINIDO') . "\n";
} else {
    echo "❌ package.json NO existe\n";
}

// 5. Verificar node_modules
echo "--- VERIFICACIÓN node_modules ---\n";
$nmPath = $baseDir . '/node_modules';
if (is_dir($nmPath)) {
    $count = count(scandir($nmPath)) - 2;
    echo "✅ node_modules existe ($count módulos)\n";
} else {
    echo "❌ node_modules NO existe\n";
}

// 6. Verificar .env
echo "--- VERIFICACIÓN .env ---\n";
$envPath = $baseDir . '/.env';
if (file_exists($envPath)) {
    echo "✅ .env existe\n";
} else {
    echo "❌ .env NO existe\n";
    // Intentar crear .env básico
    $envContent = "PORT=3000\nNODE_ENV=production\nJWT_SECRET=mkcontroller_jwt_secret_2024\nJWT_EXPIRES_IN=24h\nDB_PATH=./backend/data\n";
    file_put_contents($envPath, $envContent);
    echo "✅ .env CREADO\n";
}

// 7. Probar carga de passenger.js con Node.js
echo "--- PRUEBA Node.js ---\n";
$nodePath = '/opt/alt/alt-nodejs20/root/usr/bin/node';
if (file_exists($nodePath)) {
    echo "✅ Node.js encontrado: $nodePath\n";
    $version = trim(shell_exec("$nodePath --version 2>/dev/null"));
    echo "   Versión: $version\n";
    
    // Probar require
    $testCode = "try { require('express'); console.log('express: OK'); } catch(e) { console.log('express: FAIL - ' + e.message); }";
    $output = shell_exec("cd $baseDir && $nodePath -e \"$testCode\" 2>&1");
    echo "   $output";
} else {
    echo "❌ Node.js NO encontrado en $nodePath\n";
}

// 8. Verificar permisos del directorio
echo "--- PERMISOS ---\n";
echo "Directorio: " . substr(sprintf('%o', fileperms($baseDir)), -4) . "\n";
echo "Propietario: " . fileowner($baseDir) . "\n";

echo "\n✅ COMPLETADO\n";
