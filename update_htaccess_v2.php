<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$htaccess = $baseDir . '/.htaccess';

$content = '# MkController v3.0
# LiteSpeed / Apache

# === PERMITIR ACCESO ===
Require all granted
Satisfy Any
Order Allow,Deny
Allow from All

# === DIRECTORIO DE INICIO ===
DirectoryIndex index.html index.php

# === PHP HANDLER ===
<FilesMatch "\.php$">
    SetHandler application/x-httpd-ea-php74
</FilesMatch>

# === ARCHIVOS SENSIBLES ===
<FilesMatch "\.(env|json|lock|md|gitignore|ps1|txt|sqlite|db)$">
    Require all denied
</FilesMatch>

# === SEGURIDAD ===
Options -Indexes -MultiViews
ServerSignature Off

# === REWRITE RULES ===
<IfModule mod_rewrite.c>
RewriteEngine On

# API: redirigir /api/* a proxy.php
RewriteRule ^api/.*$ proxy.php [L]

# SPA: archivos existentes se sirven directamente
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^ - [L]

# SPA: todo lo demas a frontend/index.html
RewriteRule ^(.*)$ frontend/index.html [L]
</IfModule>
';

$result = file_put_contents($htaccess, $content);
if ($result !== false) {
    echo "OK: .htaccess actualizado ($result bytes)\n";
} else {
    echo "ERROR: No se pudo escribir .htaccess\n";
}

// Verificar
echo "\nContenido:\n";
echo file_get_contents($htaccess);
echo "\nCOMPLETADO\n";
