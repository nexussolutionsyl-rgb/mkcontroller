<?php
/**
 * fix_bom_isp.php
 * Elimina BOM del archivo ispController.js en producción
 */
header('Content-Type: text/plain; charset=utf-8');
echo "=== Fix BOM ispController.js ===\n\n";

$filePath = __DIR__ . '/backend/controllers/ispController.js';

if (!file_exists($filePath)) {
    die("❌ Archivo no encontrado: $filePath\n");
}

$content = file_get_contents($filePath);
$len = strlen($content);

// Verificar BOM (EF BB BF)
$bom = pack('H*', 'EFBBBF');
$hasBom = substr($content, 0, 3) === $bom;

echo "Tamaño del archivo: $len bytes\n";
echo "¿Tiene BOM? " . ($hasBom ? "SÍ" : "NO") . "\n";

if ($hasBom) {
    // Eliminar BOM
    $content = substr($content, 3);
    file_put_contents($filePath, $content);
    echo "✅ BOM eliminado\n";
    
    // Verificar sintaxis con Node.js
    $nodeBin = '/opt/alt/alt-nodejs20/root/usr/bin/node';
    $cmd = "$nodeBin -c " . escapeshellarg($filePath) . " 2>&1";
    $output = [];
    exec($cmd, $output, $exitCode);
    
    echo "\nVerificación de sintaxis:\n";
    echo implode("\n", $output) . "\n";
    echo "Código de salida: $exitCode\n";
    
    if ($exitCode === 0) {
        echo "\n✅ Sintaxis válida. Reiniciando servidor...\n";
        
        // Matar procesos Node.js
        exec("pkill -f 'start.js' 2>&1", $out, $code);
        echo "pkill: código $code\n";
        
        // Eliminar PID file
        $pidFile = __DIR__ . '/node.pid';
        if (file_exists($pidFile)) unlink($pidFile);
        
        echo "\n✅ Listo. El proxy PHP reiniciará Node.js automáticamente.\n";
    } else {
        echo "\n❌ La sintaxis sigue siendo inválida.\n";
    }
} else {
    echo "No tiene BOM. Verificando sintaxis...\n";
    
    $nodeBin = '/opt/alt/alt-nodejs20/root/usr/bin/node';
    $cmd = "$nodeBin -c " . escapeshellarg($filePath) . " 2>&1";
    $output = [];
    exec($cmd, $output, $exitCode);
    
    echo implode("\n", $output) . "\n";
    echo "Código de salida: $exitCode\n";
    
    if ($exitCode !== 0) {
        echo "\n❌ Error de sintaxis detectado. Verificando primeros bytes...\n";
        $firstBytes = bin2hex(substr($content, 0, 20));
        echo "Primeros 20 bytes (hex): $firstBytes\n";
        
        // Verificar caracteres de control
        for ($i = 0; $i < min(100, $len); $i++) {
            $ord = ord($content[$i]);
            if ($ord === 0 || ($ord < 32 && $ord !== 10 && $ord !== 13 && $ord !== 9)) {
                echo "Caracter de control en posición $i: 0x" . dechex($ord) . "\n";
            }
        }
    }
}

echo "\n=== FIN ===\n";
