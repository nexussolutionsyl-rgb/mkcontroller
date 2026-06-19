<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

// .htaccess CORRECTO - SIN RewriteRule que rompa todo
$htaccess = "# MkController v3.0\n";
$htaccess .= "# Configuración básica - Passenger se configura desde cPanel\n\n";

$htaccess .= "# PHP handler\n";
$htaccess .= "<FilesMatch \"\\.php$\">\n";
$htaccess .= "    SetHandler application/x-httpd-ea-php74\n";
$htaccess .= "</FilesMatch>\n\n";

$htaccess .= "# Seguridad\n";
$htaccess .= "<IfModule mod_headers.c>\n";
$htaccess .= "    Header always set X-Frame-Options \"SAMEORIGIN\"\n";
$htaccess .= "    Header always set X-Content-Type-Options \"nosniff\"\n";
$htaccess .= "</IfModule>\n\n";

$htaccess .= "# Deny access to sensitive files\n";
$htaccess .= "<FilesMatch \"\\.(env|json|lock|md)$\">\n";
$htaccess .= "    Require all denied\n";
$htaccess .= "</FilesMatch>\n\n";

$htaccess .= "# Passenger (activado desde cPanel Node.js Selector)\n";
$htaccess .= "# PassengerEnabled On\n";
$htaccess .= "# PassengerAppType node\n";
$htaccess .= "# PassengerStartupFile passenger.js\n";

file_put_contents($baseDir . '/.htaccess', $htaccess);
echo ".htaccess RESTAURADO correctamente\n";
echo "Contenido:\n" . $htaccess;
