<?php
// Script para actualizar start.js en el servidor
// Acceder via: https://nexusmk.nexussolutionsyl.com/update_startjs.php

$targetFile = __DIR__ . '/start.js';
$backupFile = __DIR__ . '/start.js.bak';

$newContent = <<<'JAVASCRIPT'
// Entry point para MkController v3.0
// Compatible con Phusion Passenger (cPanel Node.js Selector)
// Cuando Passenger carga este módulo, NO debe llamar a app.listen()
// Passenger se encarga del listen() automáticamente

require('dotenv').config({ path: './backend/.env' });
const app = require('./backend/app');
const db = require('./backend/models/database');
const seed = require('./backend/seed');
const config = require('./backend/config/config');

/**
 * Inicializa la base de datos y ejecuta seed
 * No inicia el servidor HTTP porque Passenger lo maneja
 */
async function initialize() {
  try {
    console.log('╔═══════════════════════════════════════════╗');
    console.log('║        MkController v3.0.0               ║');
    console.log('║  Administración de Routers MikroTik      ║');
    console.log('╚═══════════════════════════════════════════╝');
    console.log('');

    // Inicializar base de datos
    console.log('[Init] Inicializando base de datos...');
    await db.initialize();

    // Ejecutar seed (crear datos iniciales si no existen)
    console.log('[Init] Verificando datos iniciales...');
    await seed();

    console.log('[Init] Inicialización completada exitosamente');
    console.log('[Init] MkController listo para servir peticiones');
  } catch (error) {
    console.error('[Init] Error fatal:', error);
    process.exit(1);
  }
}

// Inicializar y exportar la app para Passenger
initialize();

// Exportar la app Express para Passenger
// Passenger requiere que el entry point exporte la aplicación
module.exports = app;
JAVASCRIPT;

echo "<pre>";
echo "=== MkController - Actualización de start.js ===\n\n";

// Verificar si el archivo existe
if (!file_exists($targetFile)) {
    die("❌ ERROR: No se encuentra start.js en " . $targetFile . "\n");
}

// Leer contenido actual
$currentContent = file_get_contents($targetFile);
echo "📄 Contenido actual de start.js:\n";
echo "--- INICIO ---\n";
echo htmlspecialchars($currentContent);
echo "\n--- FIN ---\n\n";

// Verificar si tiene app.listen (versión antigua)
if (strpos($currentContent, 'app.listen') !== false) {
    echo "⚠️  El archivo actual TIENE app.listen() - necesita actualización\n\n";
    
    // Crear backup
    $backupResult = file_put_contents($backupFile, $currentContent);
    if ($backupResult !== false) {
        echo "✅ Backup creado: $backupFile ($backupResult bytes)\n";
    } else {
        echo "❌ Error al crear backup\n";
    }
    
    // Escribir nuevo contenido
    $writeResult = file_put_contents($targetFile, $newContent);
    if ($writeResult !== false) {
        echo "✅ start.js ACTUALIZADO exitosamente ($writeResult bytes escritos)\n\n";
    } else {
        die("❌ Error al escribir start.js\n");
    }
} else {
    echo "✅ El archivo actual NO tiene app.listen() - ya está actualizado\n";
}

// Verificar el resultado
echo "📄 Nuevo contenido de start.js:\n";
echo "--- INICIO ---\n";
echo htmlspecialchars(file_get_contents($targetFile));
echo "\n--- FIN ---\n\n";

echo "=== Proceso completado ===\n";
echo "</pre>";
