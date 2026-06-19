<?php
$envFile = '/home/nexusyl/nexusmk.nexussolutionsyl.com/.env';
$backupFile = $envFile . '.backup';

// Hacer backup
copy($envFile, $backupFile);
echo "Backup de .env creado\n";

// Leer contenido actual
$content = file_get_contents($envFile);

// Variables a agregar (solo si no existen)
$varsToAdd = [
    "NEXUSMK_DB_HOST=localhost",
    "NEXUSMK_DB_USER=nexusyl_nexusmk",
    "NEXUSMK_DB_PASSWORD=",
    "NEXUSMK_DB_NAME=nexusyl_nexusmk",
    "MYSQL_HOST=localhost",
    "MYSQL_USER=nexusyl_nexusmk",
    "MYSQL_PASSWORD=",
    "MYSQL_DATABASE=nexusyl_nexusmk"
];

$added = 0;
foreach ($varsToAdd as $var) {
    $key = explode('=', $var)[0];
    if (strpos($content, $key) === false) {
        $content .= "\n" . $var;
        echo "Agregado: $var\n";
        $added++;
    } else {
        echo "Ya existe: $key\n";
    }
}

if ($added > 0) {
    file_put_contents($envFile, $content);
    echo "=== .env actualizado ===\n";
} else {
    echo "No se necesitaron cambios\n";
}

echo "\n=== CONTENIDO FINAL DE .env ===\n";
echo file_get_contents($envFile);
echo "=== FIN .env ===\n";
?>