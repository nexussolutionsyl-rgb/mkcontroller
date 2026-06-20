<?php
/**
 * Prueba directa: crear un archivo JS desde PHP con async en objeto literal
 * y verificar si Node.js lo acepta
 */

$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$nodeBin = '/opt/alt/alt-nodejs20/root/usr/bin/node';

echo "=== PRUEBA DIRECTA DE ASYNC EN OBJETO LITERAL ===\n\n";

// Prueba 1: Objeto simple con async
echo "--- Prueba 1: Objeto simple con async ---\n";
$code1 = <<<'JS'
const obj = {
  async test() {
    return 'ok';
  }
};
console.log('P1 OK');
JS;
$f1 = $baseDir . '/test1.js';
file_put_contents($f1, $code1);
exec("$nodeBin $f1 2>&1", $out1, $code1_exit);
echo "Exit: $code1_exit\n" . implode("\n", $out1) . "\n\n";
unlink($f1);

// Prueba 2: Objeto con múltiples métodos async (como ispController)
echo "--- Prueba 2: Múltiples métodos async ---\n";
$code2 = <<<'JS'
const obj = {
  async method1() { return 1; },
  async method2() { return 2; },
  async method3() { return 3; },
  async method4() { return 4; },
  async method5() { return 5; }
};
console.log('P2 OK');
JS;
$f2 = $baseDir . '/test2.js';
file_put_contents($f2, $code2);
exec("$nodeBin $f2 2>&1", $out2, $code2_exit);
echo "Exit: $code2_exit\n" . implode("\n", $out2) . "\n\n";
unlink($f2);

// Prueba 3: El archivo completo pero generado por PHP
echo "--- Prueba 3: ispController.js regenerado por PHP ---\n";
$sourceFile = $baseDir . '/backend/controllers/ispController.js';
$content = file_get_contents($sourceFile);
$f3 = $baseDir . '/test3.js';
file_put_contents($f3, $content);
exec("$nodeBin --check $f3 2>&1", $out3, $code3_exit);
echo "Exit: $code3_exit\n" . implode("\n", $out3) . "\n\n";
unlink($f3);

// Prueba 4: Comparar el contenido del archivo con lo que PHP genera
echo "--- Prueba 4: Comparación byte a byte del archivo ---\n";
$originalContent = file_get_contents($sourceFile);
$phpGenerated = $content; // mismo contenido

// Buscar diferencias byte a byte
$diffs = [];
$minLen = min(strlen($originalContent), strlen($phpGenerated));
for ($i = 0; $i < $minLen; $i++) {
    if ($originalContent[$i] !== $phpGenerated[$i]) {
        $diffs[] = "Byte $i: orig=" . dechex(ord($originalContent[$i])) . " php=" . dechex(ord($phpGenerated[$i]));
        if (count($diffs) >= 10) break;
    }
}
if (empty($diffs)) {
    echo "✓ Contenido idéntico byte a byte\n";
} else {
    echo "✗ Diferencias encontradas:\n" . implode("\n", $diffs) . "\n";
}
echo "\n";

// Prueba 5: Verificar si el problema es con require/module.exports
echo "--- Prueba 5: Archivo con module.exports ---\n";
$code5 = <<<'JS'
const obj = {
  async test() {
    return 'ok';
  }
};
module.exports = obj;
console.log('P5 OK');
JS;
$f5 = $baseDir . '/test5.js';
file_put_contents($f5, $code5);
exec("$nodeBin $f5 2>&1", $out5, $code5_exit);
echo "Exit: $code5_exit\n" . implode("\n", $out5) . "\n\n";
unlink($f5);

// Prueba 6: Verificar si el problema es con el tamaño del archivo
echo "--- Prueba 6: Archivo grande con async methods ---\n";
$code6 = "const obj = {\n";
for ($i = 0; $i < 100; $i++) {
    $code6 .= "  async method$i() { return $i; },\n";
}
$code6 .= "};\nmodule.exports = obj;\nconsole.log('P6 OK');\n";
$f6 = $baseDir . '/test6.js';
file_put_contents($f6, $code6);
exec("$nodeBin $f6 2>&1", $out6, $code6_exit);
echo "Exit: $code6_exit\n" . implode("\n", $out6) . "\n\n";
unlink($f6);

// Prueba 7: Verificar si el problema es con require de otros módulos
echo "--- Prueba 7: Verificar require('mysql2/promise') ---\n";
$code7 = <<<'JS'
const mysql = require('mysql2/promise');
console.log('mysql2/promise loaded:', typeof mysql.createPool);
JS;
$f7 = $baseDir . '/test7.js';
file_put_contents($f7, $code7);
exec("cd $baseDir && $nodeBin $f7 2>&1", $out7, $code7_exit);
echo "Exit: $code7_exit\n" . implode("\n", $out7) . "\n\n";
unlink($f7);

// Prueba 8: Verificar si el problema es con la función getIspPool
echo "--- Prueba 8: Fragmento inicial de ispController.js ---\n";
$lines = explode("\n", $content);
$fragment = implode("\n", array_slice($lines, 0, 50)) . "\n\nconsole.log('Fragmento OK');\n";
$f8 = $baseDir . '/test8.js';
file_put_contents($f8, $fragment);
exec("$nodeBin $f8 2>&1", $out8, $code8_exit);
echo "Exit: $code8_exit\n" . implode("\n", $out8) . "\n\n";
unlink($f8);

// Prueba 9: Fragmento que contiene la línea 1435
echo "--- Prueba 9: Fragmento alrededor de línea 1435 ---\n";
$fragment9 = implode("\n", array_slice($lines, 1420, 50)) . "\n\nconsole.log('Fragmento 1435 OK');\n";
$f9 = $baseDir . '/test9.js';
file_put_contents($f9, $fragment9);
exec("$nodeBin $f9 2>&1", $out9, $code9_exit);
echo "Exit: $code9_exit\n" . implode("\n", $out9) . "\n\n";
unlink($f9);

// Prueba 10: El archivo completo pero con require de mysql2/promise comentado
echo "--- Prueba 10: Archivo completo sin require de mysql2/promise ---\n";
$modifiedContent = str_replace(
    "const mysql = require('mysql2/promise');",
    "// const mysql = require('mysql2/promise');\nconst mysql = { createPool: () => ({ getConnection: async () => ({ execute: async () => [[]], release: () => {} }) }) };",
    $content
);
$f10 = $baseDir . '/test10.js';
file_put_contents($f10, $modifiedContent);
exec("$nodeBin --check $f10 2>&1", $out10, $code10_exit);
echo "Exit: $code10_exit\n" . implode("\n", $out10) . "\n\n";
unlink($f10);

echo "=== FIN DE PRUEBAS ===\n";
