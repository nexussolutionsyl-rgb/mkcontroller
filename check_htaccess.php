<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$htaccess = $baseDir . '/.htaccess';
if (file_exists($htaccess)) {
    echo "=== .htaccess content ===\n";
    echo file_get_contents($htaccess);
    echo "\n=== EOF ===\n";
} else {
    echo ".htaccess NOT FOUND\n";
}

// Check proxy.php
$proxy = $baseDir . '/proxy.php';
if (file_exists($proxy)) {
    echo "\nproxy.php exists (" . filesize($proxy) . " bytes)\n";
} else {
    echo "\nproxy.php NOT FOUND\n";
}

// Check index.html
$index = $baseDir . '/index.html';
if (file_exists($index)) {
    echo "index.html exists (" . filesize($index) . " bytes)\n";
} else {
    echo "index.html NOT FOUND\n";
}
