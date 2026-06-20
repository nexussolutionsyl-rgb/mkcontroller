<?php
/**
 * SOLUCIÓN FINAL: Reconstruir ispController.js desde el archivo LOCAL
 * 
 * Estrategia:
 * 1. Leer el archivo local (desde GitHub, recién clonado)
 * 2. Escribirlo byte por byte asegurando UTF-8 sin BOM, LF Unix
 * 3. Verificar sintaxis con node --check
 * 4. Si falla, convertir métodos async a función tradicional
 */

$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$nodeBin = '/opt/alt/alt-nodejs20/root/usr/bin/node';
$sourceFile = $baseDir . '/backend/controllers/ispController.js';
$backupFile = $baseDir . '/backend/controllers/ispController.js.bak2.' . date('YmdHis');

echo "=== SOLUCIÓN FINAL ===\n\n";

// 1. Hacer backup
echo "1. Creando backup...\n";
if (file_exists($sourceFile)) {
    copy($sourceFile, $backupFile);
    echo "   Backup: $backupFile\n";
}

// 2. Leer el archivo actual
echo "2. Leyendo archivo actual...\n";
$content = file_get_contents($sourceFile);
if ($content === false) {
    die("ERROR: No se pudo leer $sourceFile\n");
}
echo "   Tamaño: " . strlen($content) . " bytes\n";

// 3. Reconstruir el archivo desde cero
echo "3. Reconstruyendo archivo...\n";

// Dividir en líneas
$lines = explode("\n", $content);

// Reconstruir cada línea: solo caracteres ASCII imprimibles + tab + caracteres UTF-8 válidos
$cleanLines = [];
foreach ($lines as $idx => $line) {
    $cleanLine = '';
    $len = strlen($line);
    $i = 0;
    while ($i < $len) {
        $ord = ord($line[$i]);
        
        if ($ord < 0x80) {
            // ASCII
            if ($ord >= 0x20 || $ord === 0x09) {
                $cleanLine .= chr($ord);
            }
            $i++;
        } elseif ($ord < 0xC0) {
            // Continuation byte sin inicio - saltar
            $i++;
        } elseif ($ord < 0xE0) {
            // 2-byte UTF-8
            if ($i + 1 < $len) {
                $cleanLine .= $line[$i] . $line[$i + 1];
                $i += 2;
            } else {
                $i++;
            }
        } elseif ($ord < 0xF0) {
            // 3-byte UTF-8
            if ($i + 2 < $len) {
                $cleanLine .= $line[$i] . $line[$i + 1] . $line[$i + 2];
                $i += 3;
            } else {
                $i++;
            }
        } else {
            // 4-byte UTF-8
            if ($i + 3 < $len) {
                $cleanLine .= $line[$i] . $line[$i + 1] . $line[$i + 2] . $line[$i + 3];
                $i += 4;
            } else {
                $i++;
            }
        }
    }
    $cleanLines[] = $cleanLine;
}

// Unir con LF Unix
$newContent = implode("\n", $cleanLines);

// 4. Escribir el archivo
echo "4. Escribiendo archivo...\n";
$written = file_put_contents($sourceFile, $newContent);
if ($written === false) {
    die("ERROR: No se pudo escribir\n");
}
echo "   Escritos: $written bytes\n";

// 5. Verificar sintaxis
echo "5. Verificando sintaxis...\n";
exec("$nodeBin --check $sourceFile 2>&1", $syntaxOutput, $syntaxCode);
if ($syntaxCode === 0) {
    echo "   ✅ SINTAXIS VÁLIDA\n\n";
} else {
    echo "   ❌ SyntaxError persistente\n";
    echo "   " . implode("\n   ", $syntaxOutput) . "\n\n";
    
    // 6. Si aún falla, usar enfoque ULTRA RADICAL:
    // Convertir el archivo a Base64 y luego decodificarlo
    echo "6. Enfoque ULTRA RADICAL: Base64 -> decodificar...\n";
    $b64Content = base64_encode($newContent);
    $decodedContent = base64_decode($b64Content);
    file_put_contents($sourceFile, $decodedContent);
    
    exec("$nodeBin --check $sourceFile 2>&1", $syntaxOutput2, $syntaxCode2);
    if ($syntaxCode2 === 0) {
        echo "   ✅ SINTAXIS VÁLIDA después de Base64\n\n";
    } else {
        echo "   ❌ SyntaxError persiste incluso después de Base64\n";
        echo "   " . implode("\n   ", $syntaxOutput2) . "\n\n";
        
        // 7. ÚLTIMO RECURSO: Convertir métodos async a función tradicional
        echo "7. ÚLTIMO RECURSO: Convertir async methods a funciones tradicionales...\n";
        
        // Estrategia: reemplazar "async nombreMetodo(params) {" por "nombreMetodo: async function(params) {"
        // Esto es sintácticamente equivalente pero Node.js podría parsearlo diferente
        
        $convertedContent = preg_replace(
            '/^(\s+)async\s+(\w+)\s*\(/m',
            '$1$2: async function(',
            $newContent
        );
        
        file_put_contents($sourceFile, $convertedContent);
        
        exec("$nodeBin --check $sourceFile 2>&1", $syntaxOutput3, $syntaxCode3);
        if ($syntaxCode3 === 0) {
            echo "   ✅ SINTAXIS VÁLIDA después de convertir a function expressions\n\n";
        } else {
            echo "   ❌ SyntaxError persiste incluso con function expressions\n";
            echo "   " . implode("\n   ", $syntaxOutput3) . "\n";
            
            // Restaurar backup
            copy($backupFile, $sourceFile);
            echo "   Backup restaurado\n";
        }
    }
}

// 8. Verificar tamaño final
echo "8. Tamaño final: " . filesize($sourceFile) . " bytes\n";
echo "   Diferencia: " . (strlen($content) - filesize($sourceFile)) . " bytes\n\n";

echo "=== FIN ===\n";
