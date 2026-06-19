<?php
$file = '/home/nexusyl/nexusmk.nexussolutionsyl.com/.env';
if (file_exists($file)) {
    echo "=== CONTENIDO DE .env ===\n";
    $content = file_get_contents($file);
    echo $content;
    echo "=== FIN .env ===\n";
} else {
    echo "ERROR: .env no existe\n";
}
?>