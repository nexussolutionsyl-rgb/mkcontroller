<?php
// Script para reemplazar start.js con versión compatible con Passenger
// Ejecutar: https://nexusmk.nexussolutionsyl.com/fix_startjs.php

$targetFile = __DIR__ . '/start.js';
$backupFile = __DIR__ . '/start.js.bak';

$newContent = '// Entry point para MkController v3.0
// Compatible con Phusion Passenger (cPanel Node.js Selector)
// Cuando Passenger carga este módulo, NO debe llamar a app.listen()
// Passenger se encarga del listen() automáticamente

require(\'dotenv\').config({ path: \'./backend/.env\' });
const app = require(\'./backend/app\');
const db = require(\'./backend/models/database\');
const seed = require(\'./backend/seed\');
const config = require(\'./backend/config/config\');

/**
 * Inicializa la base de datos y ejecuta seed
 * No inicia el servidor HTTP porque Passenger lo maneja
 */
async function initialize() {
  try {
    console.log(\'╔═══════════════════════════════════════════╗\');
    console.log(\'║        MkController v3.0.0               ║\');
    console.log(\'║  Administración de Routers MikroTik      ║\');
    console.log(\'╚═══════════════════════════════════════════╝\');
    console.log(\'\');

    // Inicializar base de datos
    console.log(\'[Init] Inicializando base de datos...\');
    await db.initialize();

    // Ejecutar seed (crear datos iniciales si no existen)
    console.log(\'[Init] Verificando datos iniciales...\');
    await seed();

    console.log(\'[Init] Inicialización completada exitosamente\');
    console.log(\'[Init] MkController listo para servir peticiones\');
  } catch (error) {
    console.error(\'[Init] Error fatal:\', error);
    process.exit(1);
  }
}

// Inicializar y exportar la app para Passenger
initialize();

// Exportar la app Express para Passenger
// Passenger requiere que el entry point exporte la aplicación
module.exports = app;
';

echo "<pre>\n";
echo "=== MkController - Fix start.js ===\n\n";

if (!file_exists($targetFile)) {
    die("❌ ERROR: No se encuentra " . $targetFile . "\n");
}

$currentContent = file_get_contents($targetFile);
echo "📄 Tamaño actual: " . strlen($currentContent) . " bytes\n";

// Hacer backup
file_put_contents($backupFile, $currentContent);
echo "✅ Backup creado: " . $backupFile . " (" . filesize($backupFile) . " bytes)\n";

// Escribir nuevo contenido
$written = file_put_contents($targetFile, $newContent);
if ($written !== false) {
    echo "✅ start.js ACTUALIZADO (" . $written . " bytes escritos)\n";
} else {
    die("❌ Error al escribir start.js\n");
}

// Verificar
$verifyContent = file_get_contents($targetFile);
echo "\n📄 Verificación - Nuevo contenido:\n";
echo "--- INICIO ---\n";
echo htmlspecialchars($verifyContent);
echo "\n--- FIN ---\n";

// Verificar que NO tiene app.listen
if (strpos($verifyContent, 'app.listen') === false) {
    echo "\n✅ CONFIRMADO: No contiene app.listen() - compatible con Passenger\n";
} else {
    echo "\n❌ ERROR: Todavía contiene app.listen()\n";
}

echo "\n=== Proceso completado ===\n";
echo "</pre>\n";
