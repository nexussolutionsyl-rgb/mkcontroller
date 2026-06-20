<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$file = $baseDir . '/backend/controllers/ispController.js';
$content = file_get_contents($file);
$lines = explode("\n", $content);

echo "Total líneas: " . count($lines) . "\n";
echo "Primeras 5 líneas:\n";
for ($i = 0; $i < 5; $i++) echo "  L" . ($i+1) . ": " . $lines[$i] . "\n";

echo "\nL40-L45:\n";
for ($i = 39; $i < 45; $i++) echo "  L" . ($i+1) . ": " . $lines[$i] . "\n";

echo "\nÚltimas 5 líneas:\n";
for ($i = count($lines)-5; $i < count($lines); $i++) echo "  L" . ($i+1) . ": " . $lines[$i] . "\n";

echo "\nBuscando 'ispController.' en primeras 50 líneas:\n";
for ($i = 0; $i < 50; $i++) {
    if (strpos($lines[$i], 'ispController.') !== false) {
        echo "  L" . ($i+1) . ": " . $lines[$i] . "\n";
    }
}

echo "\nBuscando 'const ispController = {' :\n";
foreach ($lines as $i => $line) {
    if (strpos($line, 'const ispController =') !== false) {
        echo "  L" . ($i+1) . ": " . $line . "\n";
    }
}

echo "\nVerificando sintaxis con node --check:\n";
$nodeBin = '/opt/alt/alt-nodejs20/root/usr/bin/node';
exec("$nodeBin --check $file 2>&1", $out, $code);
echo "Exit: $code\n" . implode("\n", $out) . "\n";
