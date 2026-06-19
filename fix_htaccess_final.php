<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

// 1. Eliminar .htaccess actual (el que tiene RewriteRule problemático)
$htaccessPath = $baseDir . '/.htaccess';
if (file_exists($htaccessPath)) {
    unlink($htaccessPath);
    echo "✅ .htaccess ELIMINADO\n";
} else {
    echo "⚠️ .htaccess no existe\n";
}

// 2. Crear nuevo .htaccess SIN RewriteRule problemático
$htaccess = "# MkController v3.0 - LiteSpeed\n";
$htaccess .= "Require all granted\n\n";

$htaccess .= "# PHP handler\n";
$htaccess .= "<FilesMatch \"\\.php$\">\n";
$htaccess .= "    SetHandler application/x-httpd-ea-php74\n";
$htaccess .= "</FilesMatch>\n\n";

$htaccess .= "# Deny access to sensitive files\n";
$htaccess .= "<FilesMatch \"\\.(env|json|lock|md|gitignore)$\">\n";
$htaccess .= "    Require all denied\n";
$htaccess .= "</FilesMatch>\n\n";

$htaccess .= "# SPA - servir frontend/index.html para rutas no encontradas\n";
$htaccess .= "<IfModule mod_rewrite.c>\n";
$htaccess .= "    RewriteEngine On\n";
$htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
$htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-d\n";
$htaccess .= "    RewriteRule ^(.*)$ frontend/index.html [L]\n";
$htaccess .= "</IfModule>\n";

file_put_contents($htaccessPath, $htaccess);
echo "✅ Nuevo .htaccess CREADO\n";
echo "Contenido:\n$htaccess\n";

// 3. Verificar
echo "\n--- VERIFICACIÓN ---\n";
echo ".htaccess existe: " . (file_exists($htaccessPath) ? "SI" : "NO") . "\n";
echo "Tamaño: " . filesize($htaccessPath) . " bytes\n";
echo "Contenido:\n" . file_get_contents($htaccessPath) . "\n";

echo "\n✅ COMPLETADO\n";
