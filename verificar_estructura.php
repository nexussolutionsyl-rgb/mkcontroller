<?php
/**
 * Verificar la estructura de ispController.js
 * Busca desbalance de llaves {}, paréntesis (), y corchetes []
 */

$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$sourceFile = $baseDir . '/backend/controllers/ispController.js';

echo "=== VERIFICACIÓN DE ESTRUCTURA ===\n\n";

$content = file_get_contents($sourceFile);
$lines = explode("\n", $content);

// 1. Balance de llaves, paréntesis y corchetes
echo "--- 1. Balance de símbolos ---\n";
$braces = 0;      // {}
$parens = 0;      // ()
$brackets = 0;    // []
$inString = false;
$inTemplate = false;
$inRegex = false;
$stringChar = '';
$templateDepth = 0;
$inBlockComment = false;
$inLineComment = false;

$braceLines = []; // registrar líneas donde cambia el balance de llaves

foreach ($lines as $idx => $line) {
    $lineNum = $idx + 1;
    $inLineComment = false;
    
    for ($i = 0; $i < strlen($line); $i++) {
        $ch = $line[$i];
        $next = $i + 1 < strlen($line) ? $line[$i + 1] : '';
        
        // Manejar comentarios de bloque
        if (!$inString && !$inTemplate && !$inLineComment) {
            if (!$inBlockComment && $ch === '/' && $next === '*') {
                $inBlockComment = true;
                $i++;
                continue;
            }
            if ($inBlockComment && $ch === '*' && $next === '/') {
                $inBlockComment = false;
                $i++;
                continue;
            }
            if ($inBlockComment) continue;
            
            // Comentarios de línea
            if ($ch === '/' && $next === '/') {
                $inLineComment = true;
                break; // resto de la línea es comentario
            }
        }
        
        if ($inBlockComment || $inLineComment) continue;
        
        // Manejar strings
        if (!$inTemplate && !$inString) {
            if ($ch === '"' || $ch === "'" || $ch === '`') {
                $inString = true;
                $stringChar = $ch;
                if ($ch === '`') $inTemplate = true;
                continue;
            }
        } elseif ($inString) {
            if ($ch === '\\' && $i + 1 < strlen($line)) {
                $i++; // skip escaped char
                continue;
            }
            if ($ch === $stringChar) {
                $inString = false;
                $stringChar = '';
                if ($inTemplate) $inTemplate = false;
            }
            continue;
        }
        
        // Contar símbolos
        if ($ch === '{') {
            $braces++;
            $braceLines[] = ['open', $lineNum, $braces];
        } elseif ($ch === '}') {
            $braces--;
            $braceLines[] = ['close', $lineNum, $braces];
        } elseif ($ch === '(') {
            $parens++;
        } elseif ($ch === ')') {
            $parens--;
        } elseif ($ch === '[') {
            $brackets++;
        } elseif ($ch === ']') {
            $brackets--;
        }
    }
}

echo "Llaves {}: $braces (debe ser 0)\n";
echo "Paréntesis (): $parens (debe ser 0)\n";
echo "Corchetes []: $brackets (debe ser 0)\n\n";

if ($braces !== 0) {
    echo "⚠ DESBALANCE DE LLAVES!\n";
    // Mostrar las últimas 20 operaciones de llaves
    $lastOps = array_slice($braceLines, -20);
    foreach ($lastOps as $op) {
        echo "  L{$op[1]}: {$op[0]} -> balance={$op[2]}\n";
    }
    echo "\n";
}

// 2. Verificar que el objeto ispController está correctamente formado
echo "--- 2. Verificar estructura del objeto ispController ---\n";
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
echo "Inicio del objeto: L" . ($objectStart + 1) . "\n";
echo "Fin del objeto (module.exports): L" . ($objectEnd + 1) . "\n";
echo "Total líneas: " . count($lines) . "\n\n";

// 3. Verificar que todas las funciones tienen su try/catch balanceado
echo "--- 3. Verificar async methods ---\n";
$asyncMethods = [];
foreach ($lines as $idx => $line) {
    if (preg_match('/^\s+async\s+\w+\s*\(/', $line)) {
        $asyncMethods[] = $idx + 1;
    }
}
echo "Total async methods: " . count($asyncMethods) . "\n";
echo "Líneas: " . implode(', ', $asyncMethods) . "\n\n";

// 4. Buscar posibles problemas: comas faltantes entre métodos
echo "--- 4. Buscar problemas de sintaxis ---\n";
$prevLineWasMethod = false;
$issues = [];
foreach ($lines as $idx => $line) {
    $trimmed = trim($line);
    $lineNum = $idx + 1;
    
    // Detectar si una línea termina un método (cierre de función con })
    if ($trimmed === '},' || $trimmed === '}') {
        // Verificar que la siguiente línea no vacía sea otro método o cierre del objeto
        $nextNonEmpty = '';
        for ($j = $idx + 1; $j < count($lines); $j++) {
            $t = trim($lines[$j]);
            if (!empty($t)) {
                $nextNonEmpty = $t;
                break;
            }
        }
        // Si el método termina con } (sin coma) y lo siguiente no es cierre del objeto
        if ($trimmed === '}' && $nextNonEmpty !== '};' && !empty($nextNonEmpty)) {
            $issues[] = "L$lineNum: Posible coma faltante después de '}'";
        }
    }
}

if (empty($issues)) {
    echo "✓ No se encontraron problemas evidentes\n";
} else {
    echo "⚠ Problemas encontrados:\n" . implode("\n", $issues) . "\n";
}
echo "\n";

// 5. Verificar el contenido de las primeras y últimas líneas
echo "--- 5. Primeras y últimas líneas ---\n";
echo "Primeras 5 líneas:\n";
for ($i = 0; $i < 5 && $i < count($lines); $i++) {
    echo "  L" . ($i + 1) . ": " . $lines[$i] . "\n";
}
echo "Últimas 5 líneas:\n";
for ($i = max(0, count($lines) - 5); $i < count($lines); $i++) {
    echo "  L" . ($i + 1) . ": " . $lines[$i] . "\n";
}
echo "\n";

// 6. Verificar si hay algún error ANTES de la línea 1435
// Buscar el primer async method y verificar el contexto
echo "--- 6. Primer método async ---\n";
$firstAsyncLine = $asyncMethods[0] ?? 0;
echo "Primer async method en L$firstAsyncLine\n";
if ($firstAsyncLine > 0) {
    echo "Contexto:\n";
    for ($i = max(0, $firstAsyncLine - 3); $i < min(count($lines), $firstAsyncLine + 3); $i++) {
        echo "  L" . ($i + 1) . ": " . $lines[$i] . "\n";
    }
}
echo "\n";

echo "=== FIN ===\n";
