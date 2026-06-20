<?php
/**
 * WORKAROUND: Convertir ispController.js para evitar el bug de Node.js
 * 
 * Teoría: Node.js v20.20.2 tiene un bug con objetos literales que contienen
 * muchos métodos async. La solución es convertir la estructura para que
 * Node.js la parse correctamente.
 * 
 * Estrategia: En lugar de:
 *   const ispController = {
 *     async method() { ... },
 *     async method2() { ... },
 *     ...
 *   };
 * 
 * Usar:
 *   const ispController = {};
 *   ispController.method = async function() { ... };
 *   ispController.method2 = async function() { ... };
 *   ...
 *   module.exports = ispController;
 */

$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$nodeBin = '/opt/alt/alt-nodejs20/root/usr/bin/node';
$sourceFile = $baseDir . '/backend/controllers/ispController.js';
$backupFile = $baseDir . '/backend/controllers/ispController.js.bak3.' . date('YmdHis');

echo "=== WORKAROUND: CONVERTIR ESTRUCTURA ===\n\n";

// 1. Backup
echo "1. Creando backup...\n";
copy($sourceFile, $backupFile);
echo "   Backup: $backupFile\n";

// 2. Leer archivo
echo "2. Leyendo archivo...\n";
$content = file_get_contents($sourceFile);
$lines = explode("\n", $content);
echo "   Líneas: " . count($lines) . "\n";

// 3. Analizar la estructura
// Buscar: const ispController = { ... };
// Y convertir a: const ispController = {}; ... ispController.method = async function() {};

$newLines = [];
$inObject = false;
$objectMethods = []; // nombre => [startLine, body]
$currentMethod = null;
$braceDepth = 0;
$methodStartLine = 0;
$objectStartLine = 0;
$objectEndLine = 0;

// Primera pasada: identificar la estructura
foreach ($lines as $idx => $line) {
    $lineNum = $idx + 1;
    
    if (strpos($line, 'const ispController = {') !== false) {
        $inObject = true;
        $objectStartLine = $idx;
        $braceDepth = 1;
        continue;
    }
    
    if ($inObject) {
        // Contar llaves
        for ($i = 0; $i < strlen($line); $i++) {
            if ($line[$i] === '{') $braceDepth++;
            if ($line[$i] === '}') $braceDepth--;
        }
        
        if ($braceDepth === 0) {
            // Fin del objeto
            $objectEndLine = $idx;
            break;
        }
    }
}

echo "3. Objeto ispController: L" . ($objectStartLine + 1) . " a L" . ($objectEndLine + 1) . "\n";

// Segunda pasada: extraer métodos y reconstruir
echo "4. Reconstruyendo archivo...\n";

// Parte 1: Todo antes del objeto (incluyendo imports, getIspPool, etc.)
$beforeObject = array_slice($lines, 0, $objectStartLine);
// Parte 2: Las líneas del objeto
$objectLines = array_slice($lines, $objectStartLine + 1, $objectEndLine - $objectStartLine - 1);
// Parte 3: Todo después del objeto (module.exports, etc.)
$afterObject = array_slice($lines, $objectEndLine + 1);

// Extraer métodos del objeto
$methods = [];
$currentMethodName = '';
$currentMethodBody = [];
$methodBraceDepth = 0;
$inMethod = false;

foreach ($objectLines as $idx => $line) {
    $trimmed = trim($line);
    
    if (!$inMethod) {
        // Buscar inicio de método: async nombre(params) {
        if (preg_match('/^\s*async\s+(\w+)\s*\(/', $line, $matches)) {
            $currentMethodName = $matches[1];
            $currentMethodBody = [$line];
            $inMethod = true;
            $methodBraceDepth = 0;
            // Contar llaves en esta línea
            for ($i = 0; $i < strlen($line); $i++) {
                if ($line[$i] === '{') $methodBraceDepth++;
                if ($line[$i] === '}') $methodBraceDepth--;
            }
            if ($methodBraceDepth === 0) {
                // Método de una sola línea (raro pero posible)
                $methods[$currentMethodName] = $currentMethodBody;
                $inMethod = false;
            }
        }
        // Si no es un método, ignorar (comentarios, etc.)
    } else {
        $currentMethodBody[] = $line;
        for ($i = 0; $i < strlen($line); $i++) {
            if ($line[$i] === '{') $methodBraceDepth++;
            if ($line[$i] === '}') $methodBraceDepth--;
        }
        if ($methodBraceDepth === 0) {
            // Fin del método
            $methods[$currentMethodName] = $currentMethodBody;
            $inMethod = false;
        }
    }
}

echo "   Métodos encontrados: " . count($methods) . "\n";

// Reconstruir el archivo
$newContent = '';

// Parte 1: imports, getIspPool, etc.
foreach ($beforeObject as $line) {
    $newContent .= $line . "\n";
}

// En lugar de "const ispController = {", crear objeto vacío
$newContent .= "const ispController = {};\n\n";

// Agregar cada método como asignación
foreach ($methods as $methodName => $methodLines) {
    // La primera línea del método es "  async nombre(params) {"
    // Convertir a: "ispController.nombre = async function(params) {"
    $firstLine = $methodLines[0];
    $convertedFirst = preg_replace(
        '/^(\s*)async\s+(\w+)\s*(\(.*\)\s*\{)/',
        'ispController.$2 = async function$3',
        $firstLine
    );
    
    // Si no se pudo convertir (por si acaso), usar el original
    if ($convertedFirst === $firstLine) {
        $convertedFirst = str_replace('async ', 'ispController.' . $methodName . ' = async function', $firstLine);
    }
    
    $newContent .= $convertedFirst . "\n";
    
    // El resto del cuerpo (excepto la última línea que es "  }" o "  },")
    for ($i = 1; $i < count($methodLines) - 1; $i++) {
        $newContent .= $methodLines[$i] . "\n";
    }
    
    // Última línea: "  }" o "  },"
    $lastLine = $methodLines[count($methodLines) - 1];
    $lastLine = rtrim($lastLine, ','); // quitar coma si existe
    $newContent .= $lastLine . "\n\n";
}

// Parte 3: module.exports
foreach ($afterObject as $line) {
    $newContent .= $line . "\n";
}

// 5. Escribir archivo
echo "5. Escribiendo archivo...\n";
file_put_contents($sourceFile, $newContent);
echo "   Escritos: " . strlen($newContent) . " bytes\n";

// 6. Verificar sintaxis
echo "6. Verificando sintaxis...\n";
exec("$nodeBin --check $sourceFile 2>&1", $syntaxOutput, $syntaxCode);
if ($syntaxCode === 0) {
    echo "   ✅ SINTAXIS VÁLIDA\n\n";
} else {
    echo "   ❌ " . implode("\n   ", $syntaxOutput) . "\n\n";
}

echo "=== FIN ===\n";
