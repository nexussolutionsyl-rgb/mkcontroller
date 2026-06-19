<?php
/**
 * ============================================================
 * 🚀 DEPLOY WEBHOOK - MKController v3.0
 * ============================================================
 * 
 * Este script se ejecuta cuando GitHub envía un webhook push.
 * Hace git pull y reinstala dependencias.
 * 
 * Configuración en GitHub:
 *   - Payload URL: https://nexusmk.nexussolutionsyl.com/deploy-webhook.php
 *   - Content type: application/json
 *   - Secret: (elige una clave secreta)
 *   - Events: Just the push event
 * ============================================================
 */

// ─── CONFIGURACIÓN ───────────────────────────────────────────
$secret = getenv('WEBHOOK_SECRET') ?: 'cambia-esta-clave-secreta';
$repoPath = __DIR__; // Asume que el webhook está en la raíz del proyecto
$branch = 'master';
$logFile = __DIR__ . '/deploy-webhook.log';

// ─── RUTAS DEL SERVIDOR (cPanel con alt-nodejs16) ──────────
// Node.js y Passenger no están en el PATH normal de cPanel
$nodeBin = '/opt/alt/alt-nodejs16/root/usr/bin';
$passengerBin = '/opt/cpanel/ea-ruby27/root/usr/bin';
$pathEnv = "/home/nexusyl/.local/bin:/home/nexusyl/bin:/usr/local/bin:/usr/bin:$nodeBin:$passengerBin";
// ─────────────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────

// Headers de respuesta
header('Content-Type: application/json');

// Función para logging
function logMsg($msg) {
    global $logFile;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
}

// Función para ejecutar comandos con PATH extendido
function runCmd($cmd, &$output = []) {
    global $pathEnv;
    $resultCode = 0;
    $result = [];
    // Prepend las rutas de Node.js y Passenger al PATH
    $fullCmd = "export PATH=\"$pathEnv:\$PATH\" && $cmd";
    exec($fullCmd . ' 2>&1', $result, $resultCode);
    $output = implode("\n", $result);
    return $resultCode;
}

logMsg("=== WEBHOOK RECEIVED ===");

// 1. Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    logMsg("ERROR: Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    exit;
}

// 2. Leer payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    logMsg("ERROR: Invalid payload");
    exit;
}

// 3. Verificar firma (si hay secret configurado)
if ($secret !== 'cambia-esta-clave-secreta') {
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    
    if (!hash_equals($expected, $signature)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature']);
        logMsg("ERROR: Invalid signature");
        exit;
    }
}

// 4. Verificar que sea un push a la rama correcta
$ref = $data['ref'] ?? '';
$expectedRef = "refs/heads/$branch";

if ($ref !== $expectedRef) {
    $msg = "Ignorado: push a $ref (esperado: $expectedRef)";
    echo json_encode(['status' => 'ignored', 'message' => $msg]);
    logMsg($msg);
    exit;
}

// 5. Ejecutar git pull
logMsg("Ejecutando git pull en $repoPath (rama: $branch)...");

$gitDir = $repoPath . '/.git';
if (!is_dir($gitDir)) {
    http_response_code(500);
    echo json_encode(['error' => 'No es un repositorio Git']);
    logMsg("ERROR: No es un repositorio Git en $repoPath");
    exit;
}

// git fetch + reset (más seguro que pull para entornos productivos)
$cmd1 = "cd $repoPath && git fetch origin $branch 2>&1";
$code1 = runCmd($cmd1, $out1);
logMsg("Fetch: $out1");

if ($code1 !== 0) {
    http_response_code(500);
    echo json_encode(['error' => 'Git fetch failed', 'output' => $out1]);
    logMsg("ERROR: Git fetch failed: $out1");
    exit;
}

$cmd2 = "cd $repoPath && git reset --hard origin/$branch 2>&1";
$code2 = runCmd($cmd2, $out2);
logMsg("Reset: $out2");

if ($code2 !== 0) {
    http_response_code(500);
    echo json_encode(['error' => 'Git reset failed', 'output' => $out2]);
    logMsg("ERROR: Git reset failed: $out2");
    exit;
}

// 6. Reinstalar dependencias
logMsg("Reinstalando dependencias...");
$cmd3 = "cd $repoPath/backend && npm ci --production 2>&1";
$code3 = runCmd($cmd3, $out3);
logMsg("npm ci: $out3");

// 7. Reiniciar app (Passenger)
logMsg("Reiniciando app...");
$cmd4 = "cd $repoPath && passenger-config restart-app . 2>&1";
$code4 = runCmd($cmd4, $out4);
logMsg("Restart: $out4");

// 8. Respuesta exitosa
$commitMsg = $data['head_commit']['message'] ?? 'unknown';
$author = $data['head_commit']['author']['name'] ?? 'unknown';
$commitId = substr($data['head_commit']['id'] ?? '', 0, 8);

$result = [
    'status' => 'success',
    'commit' => $commitId,
    'message' => $commitMsg,
    'author' => $author,
    'output' => [
        'fetch' => $out1,
        'reset' => $out2,
        'npm' => $out3,
        'restart' => $out4
    ]
];

echo json_encode($result, JSON_PRETTY_PRINT);
logMsg("✅ Deploy completado: $commitId - $commitMsg por $author");
