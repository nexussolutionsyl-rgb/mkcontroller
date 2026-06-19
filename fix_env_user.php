<?php
$envFile = '/home/nexusyl/nexusmk.nexussolutionsyl.com/.env';
$content = file_get_contents($envFile);

// Reemplazar usuario y contraseña
$content = preg_replace('/NEXUSMK_DB_USER=.*/', 'NEXUSMK_DB_USER=nexusyl_root', $content);
$content = preg_replace('/NEXUSMK_DB_PASSWORD=.*/', 'NEXUSMK_DB_PASSWORD=Casita.20', $content);
$content = preg_replace('/MYSQL_USER=.*/', 'MYSQL_USER=nexusyl_root', $content);
$content = preg_replace('/MYSQL_PASSWORD=.*/', 'MYSQL_PASSWORD=Casita.20', $content);

file_put_contents($envFile, $content);
echo "=== .env actualizado ===\n";
echo file_get_contents($envFile);
echo "=== FIN .env ===\n";
?>