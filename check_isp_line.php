<?php
/**
 * check_isp_line.php
 * Lee líneas específicas de ispController.js para diagnosticar error de sintaxis
 */
header('Content-Type: text/plain; charset=utf-8');

$filePath = __DIR__ . '/backend/controllers/ispController.js';
$lines = file($filePath);

echo "=== Diagnóstico ispController.js ===\n";
echo "Total líneas: " . count($lines) . "\n\n";

// Mostrar líneas 1430-1445
echo "Líneas 1430-1445:\n";
for ($i = 1429; $i < min(1445, count($lines)); $i++) {
    $lineNum = $i + 1;
    $hex = bin2hex(rtrim($lines[$i]));
    echo "  $lineNum: " . rtrim($lines[$i]) . "\n";
    echo "         hex: $hex\n";
}

echo "\n--- Buscando 'async' en el archivo ---\n";
$asyncLines = [];
foreach ($lines as $idx => $line) {
    if (strpos($line, 'async') !== false) {
        $asyncLines[] = ($idx + 1) . ": " . rtrim($line);
    }
}
echo "Total ocurrencias de 'async': " . count($asyncLines) . "\n";
foreach (array_slice($asyncLines, 0, 5) as $l) {
    echo "  $l\n";
}
if (count($asyncLines) > 5) {
    echo "  ... y " . (count($asyncLines) - 5) . " más\n";
}

echo "\n--- Verificando estructura del objeto ---\n";
$braceCount = 0;
$objectStart = 0;
$objectEnd = 0;
foreach ($lines as $idx => $line) {
    $trimmed = trim($line);
    if (strpos($trimmed, 'const ispController = {') !== false) {
        $objectStart = $idx + 1;
        echo "Inicio del objeto: línea " . ($idx + 1) . "\n";
    }
    if ($objectStart > 0) {
        $braceCount += substr_count($line, '{');
        $braceCount -= substr_count($line, '}');
        if ($braceCount === 0 && $idx + 1 > $objectStart) {
            $objectEnd = $idx + 1;
            echo "Fin del objeto (por llaves): línea " . ($idx + 1) . "\n";
            break;
        }
    }
}

echo "\n--- Últimas 10 líneas del archivo ---\n";
for ($i = max(0, count($lines) - 10); $i < count($lines); $i++) {
    echo "  " . ($i + 1) . ": " . rtrim($lines[$i]) . "\n";
}
