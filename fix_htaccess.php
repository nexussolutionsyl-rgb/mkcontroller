<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

// .htaccess CORRECTO para Passenger - SIN RewriteRule
$htaccess = "# MkController v3.0 - Passenger Configuration\n";
$htaccess .= "# NO usar RewriteRule con Passenger - Passenger maneja el enrutamiento\n\n";

$htaccess .= "# Habilitar Passenger\n";
$htaccess .= "PassengerEnabled On\n";
$htaccess .= "PassengerAppRoot $baseDir\n";
$htaccess .= "PassengerAppType node\n";
$htaccess .= "PassengerStartupFile passenger.js\n";
$htaccess .= "PassengerNodePath /opt/alt/alt-nodejs20/root/usr/bin/node\n";
$htaccess .= "PassengerFriendlyErrorPages On\n\n";

$htaccess .= "# Variables de entorno\n";
$htaccess .= "PassengerEnv NODE_ENV production\n";
$htaccess .= "PassengerEnv PORT 3000\n\n";

$htaccess .= "# Seguridad\n";
$htaccess .= "<IfModule mod_headers.c>\n";
$htaccess .= "    Header always set X-Frame-Options \"SAMEORIGIN\"\n";
$htaccess .= "    Header always set X-Content-Type-Options \"nosniff\"\n";
$htaccess .= "</IfModule>\n\n";

$htaccess .= "# PHP files handling (for maintenance scripts)\n";
$htaccess .= "<FilesMatch \"\\.php$\">\n";
$htaccess .= "    SetHandler application/x-httpd-ea-php74\n";
$htaccess .= "</FilesMatch>\n\n";

$htaccess .= "# Deny access to sensitive files\n";
$htaccess .= "<FilesMatch \"\\.(env|json|lock|md)$\">\n";
$htaccess .= "    Require all denied\n";
$htaccess .= "</FilesMatch>\n";

file_put_contents($baseDir . '/.htaccess', $htaccess);
echo ".htaccess actualizado correctamente\n";
echo "Contenido:\n" . $htaccess;
