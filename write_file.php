<?php
/**
 * Script para escribir archivos en el servidor
 * Uso: POST a este script con:
 *   path = ruta del archivo a crear
 *   content_b64 = contenido en base64
 * 
 * Ejemplo: curl -X POST -d "path=/home/nexusyl/nexusmk.nexussolutionsyl.com/test.txt&content_b64=SGVsbG8=" https://nexusmk.nexussolutionsyl.com/write_file.php
 */

header('Content-Type: application/json');

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$path = $_POST['path'] ?? '';
$contentB64 = $_POST['content_b64'] ?? '';

if (empty($path) || empty($contentB64)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'path and content_b64 required']);
    exit;
}

// Validar que la ruta esté dentro del directorio permitido
$allowedDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$realPath = realpath(dirname($path));
if ($realPath === false || strpos($realPath, $allowedDir) !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Path not allowed']);
    exit;
}

// Decodificar y escribir
$content = base64_decode($contentB64);
if ($content === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid base64']);
    exit;
}

$bytes = file_put_contents($path, $content);
if ($bytes === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to write file']);
    exit;
}

echo json_encode([
    'success' => true,
    'path' => $path,
    'bytes' => $bytes,
    'message' => 'File written successfully'
]);
