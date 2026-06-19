<?php
/**
 * Script simple para forzar reinicio del proceso Node.js
 * Solo mata el proceso en puerto 3001, proxy.php lo reiniciará automáticamente
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== REINICIO SIMPLE NODE.JS ===\n\n";

// 1. Matar proceso en puerto 3001
echo "[1] Matando proceso en puerto 3001...\n";
exec("lsof -ti:3001 2>/dev/null | xargs kill -9 2>/dev/null", $out, $code);
echo "  kill -9 puerto 3001: codigo $code\n";

// 2. Matar cualquier proceso start.js
echo "[2] Matando procesos start.js...\n";
exec("pkill -9 -f 'start\\.js' 2>/dev/null", $out, $code);
echo "  pkill start.js: codigo $code\n";

// 3. Verificar que no queden procesos
echo "[3] Verificando procesos restantes...\n";
exec("ps aux | grep -E 'node|start\\.js' | grep -v grep 2>&1", $procs);
if (empty($procs)) {
    echo "  No hay procesos Node.js activos\n";
} else {
    echo "  Procesos:\n";
    foreach ($procs as $p) {
        echo "    $p\n";
    }
}

echo "\n[4] Listo. proxy.php reiniciara el proceso automaticamente en la siguiente peticion.\n";
echo "    Espera 5 segundos y prueba el health endpoint.\n";
echo "=== FIN ===\n";
