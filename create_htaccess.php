<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

$htaccess = "# MkController v3.0\n";
$htaccess .= "# Configuración para LiteSpeed\n\n";

$htaccess .= "# Permitir acceso\n";
$htaccess .= "Require all granted\n\n";

$htaccess .= "# PHP handler\n";
$htaccess .= "<FilesMatch \"\\.php$\">\n";
$htaccess .= "    SetHandler application/x-httpd-ea-php74\n";
$htaccess .= "</FilesMatch>\n\n";

$htaccess .= "# Deny access to sensitive files\n";
$htaccess .= "<FilesMatch \"\\.(env|json|lock|md)$\">\n";
$htaccess .= "    Require all denied\n";
$htaccess .= "</FilesMatch>\n\n";

$htaccess .= "# SPA - servir index.html para rutas no encontradas\n";
$htaccess .= "<IfModule mod_rewrite.c>\n";
$htaccess .= "    RewriteEngine On\n";
$htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
$htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-d\n";
$htaccess .= "    RewriteRule ^(.*)$ frontend/index.html [L]\n";
$htaccess .= "</IfModule>\n";

file_put_contents($baseDir . '/.htaccess', $htaccess);
echo ".htaccess creado\n";
echo "Contenido:\n$htaccess";
