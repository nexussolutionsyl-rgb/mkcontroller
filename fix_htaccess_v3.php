<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

echo "=== MkController - Fix .htaccess v3 ===\n\n";

// 1. Eliminar .htaccess actual
$htaccessPath = $baseDir . '/.htaccess';
if (file_exists($htaccessPath)) {
    unlink($htaccessPath);
    echo "✅ .htaccess ELIMINADO\n";
} else {
    echo "⚠️ .htaccess no existe\n";
}

// 2. Crear nuevo .htaccess optimizado para LiteSpeed
$htaccess = "";
$htaccess .= "# MkController v3.0\n";
$htaccess .= "# LiteSpeed / Apache\n\n";

$htaccess .= "# === PERMITIR ACCESO ===\n";
$htaccess .= "Require all granted\n";
$htaccess .= "Satisfy Any\n";
$htaccess .= "Order Allow,Deny\n";
$htaccess .= "Allow from All\n\n";

$htaccess .= "# === DIRECTORIO DE INICIO ===\n";
$htaccess .= "DirectoryIndex index.html index.php\n\n";

$htaccess .= "# === PHP HANDLER ===\n";
$htaccess .= "<FilesMatch \"\\.php$\">\n";
$htaccess .= "    SetHandler application/x-httpd-ea-php74\n";
$htaccess .= "</FilesMatch>\n\n";

$htaccess .= "# === ARCHIVOS SENSIBLES ===\n";
$htaccess .= "<FilesMatch \"\\.(env|json|lock|md|gitignore|ps1|txt|sqlite|db)$\">\n";
$htaccess .= "    Require all denied\n";
$htaccess .= "</FilesMatch>\n\n";

$htaccess .= "# === SEGURIDAD ===\n";
$htaccess .= "Options -Indexes -MultiViews\n";
$htaccess .= "ServerSignature Off\n\n";

$htaccess .= "# === SPA REWRITE ===\n";
$htaccess .= "<IfModule mod_rewrite.c>\n";
$htaccess .= "    RewriteEngine On\n";
$htaccess .= "    RewriteBase /\n";
$htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
$htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-d\n";
$htaccess .= "    RewriteRule ^(.*)$ frontend/index.html [L]\n";
$htaccess .= "</IfModule>\n";

file_put_contents($htaccessPath, $htaccess);
echo "✅ Nuevo .htaccess CREADO\n";
echo "Tamaño: " . filesize($htaccessPath) . " bytes\n";
echo "--- CONTENIDO ---\n";
echo file_get_contents($htaccessPath) . "\n";

// 3. Verificar archivos importantes
echo "--- VERIFICACIONES ---\n";

$filesToCheck = [
    'passenger.js' => 'passenger.js',
    'package.json' => 'package.json',
    '.env' => '.env',
    'frontend/index.html' => 'frontend/index.html',
    'backend/app.js' => 'backend/app.js',
];

foreach ($filesToCheck as $name => $path) {
    $fullPath = $baseDir . '/' . $path;
    if (file_exists($fullPath)) {
        echo "✅ $name: OK (" . filesize($fullPath) . " bytes)\n";
    } else {
        echo "❌ $name: NO EXISTE\n";
    }
}

// 4. Verificar node_modules
$nmPath = $baseDir . '/node_modules';
if (is_dir($nmPath)) {
    $count = count(array_diff(scandir($nmPath), ['.', '..']));
    echo "✅ node_modules: $count módulos\n";
} else {
    echo "❌ node_modules: NO EXISTE\n";
}

// 5. Verificar Node.js
$nodePath = '/opt/alt/alt-nodejs20/root/usr/bin/node';
if (file_exists($nodePath)) {
    echo "✅ Node.js: $nodePath\n";
} else {
    echo "❌ Node.js: NO ENCONTRADO\n";
}

echo "\n✅ COMPLETADO\n";
