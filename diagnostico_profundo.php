<?php
/**
 * Diagnóstico profundo del error SyntaxError: Unexpected token 'async'
 * Verifica versión de Node.js, caracteres ocultos, y prueba la sintaxis
 */

$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$nodeBin = '/opt/alt/alt-nodejs20/root/usr/bin/node';
$filePath = $baseDir . '/backend/controllers/ispController.js';

echo "=== DIAGNÓSTICO PROFUNDO ===\n\n";

// 1. Verificar versión de Node.js
echo "--- 1. Versión de Node.js ---\n";
exec("$nodeBin --version 2>&1", $output, $code);
echo "Ruta: $nodeBin\n";
echo "Versión: " . implode("\n", $output) . "\n";
echo "Exit code: $code\n\n";

// 2. Verificar si hay otro node en el PATH
echo "--- 2. Node en PATH ---\n";
exec("which node 2>&1; node --version 2>&1", $output2, $code2);
echo implode("\n", $output2) . "\n";
echo "Exit code: $code2\n\n";

// 3. Verificar caracteres ocultos en el archivo (alrededor de línea 1435)
echo "--- 3. Análisis hexadecimal de líneas 1430-1440 ---\n";
$content = file_get_contents($filePath);
$lines = explode("\n", $content);
for ($i = 1429; $i <= 1439; $i++) {
    if (isset($lines[$i])) {
        $line = $lines[$i];
        $hex = bin2hex($line);
        $clean = preg_replace('/[^\x20-\x7E]/', '.', $line);
        echo "L" . ($i + 1) . ": hex=$hex\n";
        echo "     clean=$clean\n";
        echo "     len=" . strlen($line) . " bytes\n\n";
    }
}

// 4. Verificar caracteres no-ASCII en TODO el archivo
echo "--- 4. Caracteres no-ASCII en el archivo ---\n";
$nonAscii = [];
foreach ($lines as $idx => $line) {
    for ($j = 0; $j < strlen($line); $j++) {
        $ord = ord($line[$j]);
        if ($ord > 127) {
            $nonAscii[] = "L" . ($idx + 1) . ": pos=$j, char=" . $line[$j] . ", ord=$ord, hex=" . dechex($ord);
        }
    }
}
if (empty($nonAscii)) {
    echo "✓ No se encontraron caracteres no-ASCII\n\n";
} else {
    echo "✗ Se encontraron " . count($nonAscii) . " caracteres no-ASCII:\n";
    echo implode("\n", array_slice($nonAscii, 0, 20)) . "\n\n";
}

// 5. Verificar BOM en todo el archivo
echo "--- 5. Verificación de BOM ---\n";
$bom = substr($content, 0, 3);
$bomHex = bin2hex($bom);
if ($bomHex === 'efbbbf') {
    echo "✗ BOM detectado (UTF-8 BOM: EF BB BF)\n\n";
} else {
    echo "✓ No hay BOM (primeros bytes: $bomHex)\n\n";
}

// 6. Verificar caracteres de control (0x00-0x1F excepto 0x0A, 0x0D)
echo "--- 6. Caracteres de control (excepto \\n, \\r) ---\n";
$controlChars = [];
foreach ($lines as $idx => $line) {
    for ($j = 0; $j < strlen($line); $j++) {
        $ord = ord($line[$j]);
        if ($ord < 0x20 && $ord !== 0x0A && $ord !== 0x0D && $ord !== 0x09) {
            $controlChars[] = "L" . ($idx + 1) . ": pos=$j, ord=$ord, hex=" . dechex($ord);
        }
    }
}
if (empty($controlChars)) {
    echo "✓ No se encontraron caracteres de control problemáticos\n\n";
} else {
    echo "✗ Se encontraron " . count($controlChars) . " caracteres de control:\n";
    echo implode("\n", array_slice($controlChars, 0, 20)) . "\n\n";
}

// 7. Verificar final de línea (CRLF vs LF)
echo "--- 7. Finales de línea ---\n";
$crlf = substr_count($content, "\r\n");
$lf = substr_count($content, "\n") - $crlf;
echo "CRLF (\\r\\n): $crlf\n";
echo "LF (\\n): $lf\n\n";

// 8. Probar sintaxis con node --check
echo "--- 8. Prueba de sintaxis con node --check ---\n";
$tmpFile = $baseDir . '/temp_syntax_check.js';
file_put_contents($tmpFile, $content);
exec("$nodeBin --check $tmpFile 2>&1", $syntaxOutput, $syntaxCode);
echo "Exit code: $syntaxCode\n";
echo implode("\n", $syntaxOutput) . "\n";
unlink($tmpFile);
echo "\n";

// 9. Probar con un fragmento mínimo que contenga async en objeto literal
echo "--- 9. Prueba de async en objeto literal ---\n";
$testCode = <<<'JS'
const test = {
  async foo() {
    return 'bar';
  }
};
console.log(test.foo());
JS;
$tmpFile2 = $baseDir . '/temp_async_test.js';
file_put_contents($tmpFile2, $testCode);
exec("$nodeBin $tmpFile2 2>&1", $asyncOutput, $asyncCode);
echo "Exit code: $asyncCode\n";
echo implode("\n", $asyncOutput) . "\n";
unlink($tmpFile2);
echo "\n";

// 10. Probar con el archivo completo pero usando --harmony
echo "--- 10. Prueba con --harmony flags ---\n";
exec("$nodeBin --harmony --check $filePath 2>&1", $harmonyOutput, $harmonyCode);
echo "Exit code: $harmonyCode\n";
echo implode("\n", $harmonyOutput) . "\n\n";

echo "=== FIN DEL DIAGNÓSTICO ===\n";
