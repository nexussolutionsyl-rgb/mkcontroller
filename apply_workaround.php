<?php
/**
 * APLICAR WORKAROUND - SIN RESTAURAR BACKUP
 * Convierte ispController.js de objeto literal a asignaciones directas
 */

$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$nodeBin = '/opt/alt/alt-nodejs20/root/usr/bin/node';
$sourceFile = $baseDir . '/backend/controllers/ispController.js';

echo "=== APLICANDO WORKAROUND ===\n\n";

// 1. Leer archivo
echo "1. Leyendo archivo...\n";
$content = file_get_contents($sourceFile);
$lines = explode("\n", $content);
echo "   Líneas: " . count($lines) . "\n";

// 2. Encontrar el objeto ispController
$objectStart = -1;
$objectEnd = -1;
foreach ($lines as $idx => $line) {
    if (strpos($line, 'const ispController = {') !== false) {
        $objectStart = $idx;
    }
    if (strpos($line, 'module.exports = ispController;') !== false) {
        $objectEnd = $idx;
    }
}

if ($objectStart === -1 || $objectEnd === -1) {
    die("ERROR: No se encontró la estructura esperada\n");
}

echo "2. Objeto: L" . ($objectStart + 1) . " a L" . ($objectEnd + 1) . "\n";

// 3. Extraer partes
$beforeObject = array_slice($lines, 0, $objectStart);
$objectLines = array_slice($lines, $objectStart + 1, $objectEnd - $objectStart - 1);
$afterObject = array_slice($lines, $objectEnd);

// 4. Extraer métodos del objeto
$methods = [];
$currentMethodName = '';
$currentMethodBody = [];
$methodBraceDepth = 0;
$inMethod = false;

foreach ($objectLines as $idx => $line) {
    if (!$inMethod) {
        if (preg_match('/^\s*async\s+(\w+)\s*\(/', $line, $matches)) {
            $currentMethodName = $matches[1];
            $currentMethodBody = [$line];
            $inMethod = true;
            $methodBraceDepth = 0;
            for ($i = 0; $i < strlen($line); $i++) {
                if ($line[$i] === '{') $methodBraceDepth++;
                if ($line[$i] === '}') $methodBraceDepth--;
            }
            if ($methodBraceDepth === 0) {
                $methods[$currentMethodName] = $currentMethodBody;
                $inMethod = false;
            }
        }
    } else {
        $currentMethodBody[] = $line;
        for ($i = 0; $i < strlen($line); $i++) {
            if ($line[$i] === '{') $methodBraceDepth++;
            if ($line[$i] === '}') $methodBraceDepth--;
        }
        if ($methodBraceDepth === 0) {
            $methods[$currentMethodName] = $currentMethodBody;
            $inMethod = false;
        }
    }
}

echo "3. Métodos encontrados: " . count($methods) . "\n";

// 5. Reconstruir
echo "4. Reconstruyendo...\n";
$newContent = '';

// Parte 1: todo antes del objeto
foreach ($beforeObject as $line) {
    $newContent .= $line . "\n";
}

// Crear objeto vacío
$newContent .= "const ispController = {};\n\n";

// Agregar métodos como asignaciones
foreach ($methods as $methodName => $methodLines) {
    $firstLine = $methodLines[0];
    $convertedFirst = preg_replace(
        '/^(\s*)async\s+(\w+)\s*(\(.*\)\s*\{)/',
        'ispController.$2 = async function$3',
        $firstLine
    );
    
    if ($convertedFirst === $firstLine) {
        $convertedFirst = 'ispController.' . $methodName . ' = async function' . 
            substr($firstLine, strpos($firstLine, '('));
    }
    
    $newContent .= $convertedFirst . "\n";
    
    for ($i = 1; $i < count($methodLines) - 1; $i++) {
        $newContent .= $methodLines[$i] . "\n";
    }
    
    $lastLine = $methodLines[count($methodLines) - 1];
    $lastLine = rtrim($lastLine, ',');
    $newContent .= $lastLine . "\n\n";
}

// Parte 3: module.exports
foreach ($afterObject as $line) {
    $newContent .= $line . "\n";
}

// 6. Escribir
echo "5. Escribiendo archivo (" . strlen($newContent) . " bytes)...\n";
file_put_contents($sourceFile, $newContent);

// 7. Verificar
echo "6. Verificando sintaxis...\n";
exec("$nodeBin --check $sourceFile 2>&1", $out, $code);
if ($code === 0) {
    echo "   ✅ SINTAXIS VÁLIDA\n";
} else {
    echo "   ❌ " . implode("\n", $out) . "\n";
    exit(1);
}

// 8. Verificar primeras líneas
echo "\n7. Verificación:\n";
$newLines = explode("\n", $newContent);
echo "   L40: " . $newLines[39] . "\n";
echo "   L41: " . $newLines[40] . "\n";
echo "   L42: " . $newLines[41] . "\n";
echo "   Última: " . $newLines[count($newLines)-2] . "\n";

echo "\n=== WORKAROUND APLICADO ===\n";
