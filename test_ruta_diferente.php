<?php
/**
 * Prueba: copiar ispController.js a diferentes ubicaciones y verificar
 * si el problema es con la ruta específica o con el contenido
 */

$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$nodeBin = '/opt/alt/alt-nodejs20/root/usr/bin/node';
$sourceFile = $baseDir . '/backend/controllers/ispController.js';

echo "=== PRUEBA DE RUTA Y PERMISOS ===\n\n";

// 1. Verificar permisos del archivo
echo "--- 1. Permisos del archivo ---\n";
$perms = fileperms($sourceFile);
echo "Archivo: $sourceFile\n";
echo "Permisos: " . substr(sprintf('%o', $perms), -4) . "\n";
echo "Propietario: " . fileowner($sourceFile) . " (uid)\n";
echo "Grupo: " . filegroup($sourceFile) . " (gid)\n";
echo "Legible: " . (is_readable($sourceFile) ? 'Sí' : 'No') . "\n\n";

// 2. Copiar a diferentes ubicaciones
echo "--- 2. Copiar a diferentes ubicaciones ---\n";

$locations = [
    $baseDir . '/test_copy_isp.js',
    $baseDir . '/backend/test_copy_isp.js',
    '/tmp/test_copy_isp.js',
];

$content = file_get_contents($sourceFile);

foreach ($locations as $loc) {
    file_put_contents($loc, $content);
    exec("$nodeBin --check $loc 2>&1", $out, $code);
    $result = $code === 0 ? '✅ OK' : '❌ FAIL';
    echo "$result: $loc\n";
    if ($code !== 0) {
        echo "   " . implode("\n   ", $out) . "\n";
    }
    unlink($loc);
}
echo "\n";

// 3. Probar con el contenido pero con un nombre de variable diferente
echo "--- 3. Contenido con nombre de variable cambiado ---\n";
$modifiedContent = str_replace('const ispController = {', 'const ispCtrl = {', $content);
$modifiedContent = str_replace('module.exports = ispController;', 'module.exports = ispCtrl;', $modifiedContent);
$f3 = $baseDir . '/test_rename.js';
file_put_contents($f3, $modifiedContent);
exec("$nodeBin --check $f3 2>&1", $out3, $code3);
echo ($code3 === 0 ? '✅ OK' : '❌ FAIL') . ": $f3\n";
if ($code3 !== 0) echo "   " . implode("\n   ", $out3) . "\n";
unlink($f3);
echo "\n";

// 4. Probar con el contenido pero eliminando los comentarios JSDoc
echo "--- 4. Contenido sin comentarios JSDoc ---\n";
$noComments = preg_replace('/\/\*\*[\s\S]*?\*\//', '', $content);
$f4 = $baseDir . '/test_nocomments.js';
file_put_contents($f4, $noComments);
exec("$nodeBin --check $f4 2>&1", $out4, $code4);
echo ($code4 === 0 ? '✅ OK' : '❌ FAIL') . ": $f4\n";
if ($code4 !== 0) echo "   " . implode("\n   ", $out4) . "\n";
unlink($f4);
echo "\n";

// 5. Verificar si el problema es con la línea específica 1435
// Vamos a leer byte por byte alrededor de esa línea
echo "--- 5. Análisis byte a byte de línea 1435 ---\n";
$lines = explode("\n", $content);
$line1435 = $lines[1434]; // 0-indexed
echo "Contenido: '$line1435'\n";
echo "Longitud: " . strlen($line1435) . " bytes\n";
echo "Hex: " . bin2hex($line1435) . "\n";
echo "Chars:\n";
for ($i = 0; $i < strlen($line1435); $i++) {
    $ord = ord($line1435[$i]);
    $char = $line1435[$i];
    $printable = ($ord >= 0x20 && $ord <= 0x7E) ? $char : '.';
    echo "  pos=$i: ord=$ord hex=" . dechex($ord) . " char='$printable'\n";
}
echo "\n";

// 6. Verificar si hay un error de sintaxis REAL mirando el contexto completo
// alrededor de la línea 1435
echo "--- 6. Contexto completo alrededor de línea 1435 ---\n";
for ($i = 1430; $i <= 1445; $i++) {
    $lineNum = $i + 1;
    $line = $lines[$i] ?? '(undefined)';
    echo "L$lineNum: $line\n";
}
echo "\n";

// 7. Probar con node --check usando un enfoque diferente: 
// crear un wrapper que require el archivo
echo "--- 7. Probar con require wrapper ---\n";
$wrapperCode = <<<'JS'
try {
  const ctrl = require('./backend/controllers/ispController');
  console.log('Require exitoso');
} catch(e) {
  console.log('Error:', e.message);
}
JS;
$f7 = $baseDir . '/test_wrapper.js';
file_put_contents($f7, $wrapperCode);
exec("cd $baseDir && $nodeBin test_wrapper.js 2>&1", $out7, $code7);
echo "Exit: $code7\n" . implode("\n", $out7) . "\n\n";
unlink($f7);

echo "=== FIN ===\n";
