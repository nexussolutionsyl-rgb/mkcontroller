<?php
/**
 * Verifica qué archivos .env existen y su contenido
 */
header('Content-Type: text/plain; charset=utf-8');

$appDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

echo "=== VERIFICACION DE ARCHIVOS .env ===\n\n";

// 1. Revisar backend/.env
$backendEnv = $appDir . '/backend/.env';
echo "[1] backend/.env:\n";
if (file_exists($backendEnv)) {
    echo "  EXISTE\n";
    echo "  Contenido:\n";
    $content = file_get_contents($backendEnv);
    foreach (explode("\n", $content) as $line) {
        $line = trim($line);
        if ($line && !str_starts_with($line, '#')) {
            echo "    $line\n";
        }
    }
} else {
    echo "  NO EXISTE\n";
}

echo "\n";

// 2. Revisar .env (raíz)
$rootEnv = $appDir . '/.env';
echo "[2] .env (raiz):\n";
if (file_exists($rootEnv)) {
    echo "  EXISTE\n";
    echo "  Contenido:\n";
    $content = file_get_contents($rootEnv);
    foreach (explode("\n", $content) as $line) {
        $line = trim($line);
        if ($line && !str_starts_with($line, '#')) {
            echo "    $line\n";
        }
    }
} else {
    echo "  NO EXISTE\n";
}

echo "\n";

// 3. Revisar backend/.env.example
$exampleEnv = $appDir . '/backend/.env.example';
echo "[3] backend/.env.example:\n";
if (file_exists($exampleEnv)) {
    echo "  EXISTE\n";
    $content = file_get_contents($exampleEnv);
    foreach (explode("\n", $content) as $line) {
        $line = trim($line);
        if ($line && !str_starts_with($line, '#')) {
            echo "    $line\n";
        }
    }
} else {
    echo "  NO EXISTE\n";
}

echo "\n=== FIN ===\n";
