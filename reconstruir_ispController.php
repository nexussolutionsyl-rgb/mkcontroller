<?php
/**
 * Reconstruye ispController.js desde cero para eliminar cualquier carácter oculto
 * que esté causando SyntaxError: Unexpected token 'async'
 */

$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$sourceFile = $baseDir . '/backend/controllers/ispController.js';
$backupFile = $baseDir . '/backend/controllers/ispController.js.bak.' . date('YmdHis');
$outputFile = $baseDir . '/backend/controllers/ispController.js';

echo "=== RECONSTRUCCIÓN DE ispController.js ===\n\n";

// 1. Leer el archivo original
echo "1. Leyendo archivo original...\n";
$content = file_get_contents($sourceFile);
if ($content === false) {
    die("ERROR: No se pudo leer $sourceFile\n");
}
echo "   Tamaño original: " . strlen($content) . " bytes\n";

// 2. Hacer backup
echo "2. Creando backup...\n";
copy($sourceFile, $backupFile);
echo "   Backup creado: $backupFile\n";

// 3. Reconstruir el archivo línea por línea, sanitizando cada línea
echo "3. Reconstruyendo archivo...\n";
$lines = explode("\n", $content);
$cleanLines = [];
$problematicLines = [];

foreach ($lines as $idx => $line) {
    $lineNum = $idx + 1;
    $originalLine = $line;
    
    // Eliminar caracteres de control (excepto \t)
    $cleaned = '';
    for ($i = 0; $i < strlen($line); $i++) {
        $ord = ord($line[$i]);
        if ($ord >= 0x20 || $ord === 0x09) {
            $cleaned .= $line[$i];
        } else {
            $problematicLines[] = "L$lineNum: pos=$i, ord=$ord, hex=" . dechex($ord);
        }
    }
    
    $cleanLines[] = $cleaned;
}

if (!empty($problematicLines)) {
    echo "   ⚠ Se encontraron " . count($problematicLines) . " líneas con caracteres de control:\n";
    foreach (array_slice($problematicLines, 0, 20) as $p) {
        echo "     $p\n";
    }
} else {
    echo "   ✓ No se encontraron caracteres de control\n";
}

// 4. Escribir archivo limpio
$newContent = implode("\n", $cleanLines);
$written = file_put_contents($outputFile, $newContent);
if ($written === false) {
    die("ERROR: No se pudo escribir $outputFile\n");
}
echo "4. Archivo reconstruido: $written bytes\n\n";

// 5. Verificar que se escribió correctamente
echo "5. Verificando integridad...\n";
$verifyContent = file_get_contents($outputFile);
$verifyLines = explode("\n", $verifyContent);
echo "   Líneas originales: " . count($lines) . "\n";
echo "   Líneas nuevas: " . count($verifyLines) . "\n";
echo "   Tamaño nuevo: " . strlen($verifyContent) . " bytes\n\n";

// 6. Probar sintaxis con node --check
echo "6. Probando sintaxis con node --check...\n";
$nodeBin = '/opt/alt/alt-nodejs20/root/usr/bin/node';
exec("$nodeBin --check $outputFile 2>&1", $syntaxOutput, $syntaxCode);
echo "   Exit code: $syntaxCode\n";
if ($syntaxCode === 0) {
    echo "   ✅ SINTAXIS VÁLIDA\n\n";
} else {
    echo "   ❌ " . implode("\n", $syntaxOutput) . "\n\n";
    
    // 7. Si aún falla, intentar enfoque más agresivo: reescribir byte por byte
    echo "7. Enfoque agresivo: reescribir byte por byte...\n";
    $fh = fopen($outputFile, 'wb');
    if ($fh) {
        foreach ($cleanLines as $i => $cleanLine) {
            // Escribir cada línea como bytes ASCII puros
            for ($j = 0; $j < strlen($cleanLine); $j++) {
                $ord = ord($cleanLine[$j]);
                if ($ord >= 0x20 && $ord <= 0x7E) {
                    fwrite($fh, chr($ord));
                } elseif ($ord === 0x09) {
                    fwrite($fh, chr(0x09)); // tab
                } else {
                    // Carácter no-ASCII (acentos, etc.) - escribir como UTF-8 válido
                    fwrite($fh, $cleanLine[$j]);
                }
            }
            if ($i < count($cleanLines) - 1) {
                fwrite($fh, "\n");
            }
        }
        fclose($fh);
        echo "   Archivo reescrito byte por byte\n";
        
        // Probar de nuevo
        exec("$nodeBin --check $outputFile 2>&1", $syntaxOutput2, $syntaxCode2);
        echo "   Exit code: $syntaxCode2\n";
        if ($syntaxCode2 === 0) {
            echo "   ✅ SINTAXIS VÁLIDA después de reescritura agresiva\n\n";
        } else {
            echo "   ❌ " . implode("\n", $syntaxOutput2) . "\n\n";
        }
    }
}

// 8. Mostrar diff de tamaño
echo "8. Comparación de tamaños:\n";
echo "   Original: " . strlen($content) . " bytes\n";
echo "   Reconstruido: " . filesize($outputFile) . " bytes\n";
echo "   Diferencia: " . (strlen($content) - filesize($outputFile)) . " bytes\n\n";

echo "=== RECONSTRUCCIÓN COMPLETADA ===\n";
