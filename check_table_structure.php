<?php
/**
 * Verifica la estructura de las tablas de nexusMK en la base de datos
 */
header('Content-Type: text/plain; charset=utf-8');

$host = 'localhost';
$user = 'nexusyl_root';
$pass = 'Casita.2026';
$db = 'nexusyl_nexusmk';

echo "=== ESTRUCTURA DE TABLAS NEXUSMK ===\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Listar tablas
    echo "[1] Tablas en la base de datos '$db':\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    echo "\n[2] Estructura de tablas relevantes:\n";
    $targetTables = ['dispositivos_mikrotik', 'interfaces_mikrotik', 'peers_mikrotik', 'reglas_firewall'];
    
    foreach ($targetTables as $table) {
        if (in_array($table, $tables)) {
            echo "\n  --- $table ---\n";
            $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $col) {
                echo "    {$col['Field']} ({$col['Type']}) [{$col['Key']}] {$col['Extra']}\n";
            }
        } else {
            echo "\n  --- $table: NO EXISTE ---\n";
        }
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN ===\n";
