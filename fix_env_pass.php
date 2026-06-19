<?php
$envFile = '/home/nexusyl/nexusmk.nexussolutionsyl.com/.env';
$content = file_get_contents($envFile);

// Reemplazar las líneas de password
$content = preg_replace('/NEXUSMK_DB_PASSWORD=.*/', 'NEXUSMK_DB_PASSWORD=Casita.20', $content);
$content = preg_replace('/MYSQL_PASSWORD=.*/', 'MYSQL_PASSWORD=Casita.20', $content);

file_put_contents($envFile, $content);
echo "=== .env actualizado con contraseña MySQL ===\n";
echo file_get_contents($envFile);
echo "=== FIN .env ===\n";
?>