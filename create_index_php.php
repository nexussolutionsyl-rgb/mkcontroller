<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

echo "=== Creando index.php en la raíz ===\n\n";

// 1. Crear index.php que sirva como entry point
$indexContent = '<?php
/**
 * MkController v3.0 - Entry Point
 * Redirige al frontend SPA
 */

// Si es una solicitud de API, redirigir al backend
$requestUri = $_SERVER["REQUEST_URI"] ?? "/";

// Archivos estáticos - servir directamente
$staticExtensions = ["css", "js", "png", "jpg", "jpeg", "gif", "svg", "ico", "woff", "woff2", "ttf", "eot"];
$ext = strtolower(pathinfo(parse_url($requestUri, PHP_URL_PATH), PATHINFO_EXTENSION));

if (in_array($ext, $staticExtensions)) {
    return false; // Dejar que LiteSpeed sirva el archivo
}

// API requests - redirigir al backend
if (strpos($requestUri, "/api/") === 0) {
    $apiUrl = "http://localhost:3001" . $requestUri;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // Copiar método HTTP y body
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER["REQUEST_METHOD"]);
    if ($_SERVER["REQUEST_METHOD"] === "POST" || $_SERVER["REQUEST_METHOD"] === "PUT") {
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
    }
    
    // Copiar headers relevantes
    $headers = [];
    if (isset($_SERVER["HTTP_AUTHORIZATION"])) {
        $headers[] = "Authorization: " . $_SERVER["HTTP_AUTHORIZATION"];
    }
    if (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
        $headers[] = "Content-Type: " . $_SERVER["HTTP_CONTENT_TYPE"];
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    
    if ($response !== false) {
        $body = substr($response, $headerSize);
        http_response_code($httpCode);
        header("Content-Type: application/json");
        echo $body;
        exit;
    }
    
    http_response_code(502);
    echo json_encode(["error" => "Backend no disponible"]);
    exit;
}

// SPA - servir frontend/index.html para todas las demás rutas
$frontendFile = __DIR__ . "/frontend/index.html";
if (file_exists($frontendFile)) {
    readfile($frontendFile);
    exit;
}

// Fallback
http_response_code(404);
echo "Not Found";
';

$indexPath = $baseDir . '/index.php';
file_put_contents($indexPath, $indexContent);
echo "✅ index.php CREADO (" . filesize($indexPath) . " bytes)\n";

// 2. Actualizar .htaccess para incluir index.php como DirectoryIndex
$htaccessPath = $baseDir . '/.htaccess';
$htaccess = "";
$htaccess .= "# MkController v3.0\n";
$htaccess .= "# LiteSpeed / Apache\n\n";

$htaccess .= "# === PERMITIR ACCESO ===\n";
$htaccess .= "Require all granted\n";
$htaccess .= "Satisfy Any\n";
$htaccess .= "Order Allow,Deny\n";
$htaccess .= "Allow from All\n\n";

$htaccess .= "# === DIRECTORIO DE INICIO ===\n";
$htaccess .= "DirectoryIndex index.php index.html\n\n";

$htaccess .= "# === PHP HANDLER ===\n";
$htaccess .= "<FilesMatch \"\\.php$\">\n";
$htaccess .= "    SetHandler application/x-httpd-ea-php74\n";
$htaccess .= "</FilesMatch>\n\n";

$htaccess .= "# === ARCHIVOS SENSIBLES ===\n";
$htaccess .= "<FilesMatch \"\\.(env|json|lock|md|gitignore|ps1|txt|sqlite|db|js|mjs)$\">\n";
$htaccess .= "    Require all denied\n";
$htaccess .= "</FilesMatch>\n\n";

$htaccess .= "# === SEGURIDAD ===\n";
$htaccess .= "Options -Indexes -MultiViews\n";
$htaccess .= "ServerSignature Off\n\n";

$htaccess .= "# === SPA REWRITE ===\n";
$htaccess .= "<IfModule mod_rewrite.c>\n";
$htaccess .= "    RewriteEngine On\n";
$htaccess .= "    RewriteBase /\n";
$htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
$htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-d\n";
$htaccess .= "    RewriteRule ^(.*)$ index.php [L]\n";
$htaccess .= "</IfModule>\n";

file_put_contents($htaccessPath, $htaccess);
echo "✅ .htaccess ACTUALIZADO (" . filesize($htaccessPath) . " bytes)\n";

// 3. Verificar
echo "\n--- VERIFICACIÓN ---\n";
echo "index.php: " . (file_exists($indexPath) ? "✅" : "❌") . "\n";
echo ".htaccess: " . (file_exists($htaccessPath) ? "✅" : "❌") . "\n";
echo "frontend/index.html: " . (file_exists($baseDir . '/frontend/index.html') ? "✅" : "❌") . "\n";

echo "\n✅ COMPLETADO\n";
