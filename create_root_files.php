<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

echo "=== MkController - Crear archivos raíz ===\n\n";

// 1. Crear index.php
$indexContent = <<<'PHPCODE'
<?php
/**
 * MkController v3.0 - Entry Point
 */
$requestUri = $_SERVER["REQUEST_URI"] ?? "/";

// API requests - proxy al backend Node.js
if (strpos($requestUri, "/api/") === 0) {
    $apiUrl = "http://localhost:3001" . $requestUri;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER["REQUEST_METHOD"]);
    if ($_SERVER["REQUEST_METHOD"] === "POST" || $_SERVER["REQUEST_METHOD"] === "PUT") {
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
    }
    $h = [];
    if (isset($_SERVER["HTTP_AUTHORIZATION"])) $h[] = "Authorization: " . $_SERVER["HTTP_AUTHORIZATION"];
    if (isset($_SERVER["HTTP_CONTENT_TYPE"])) $h[] = "Content-Type: " . $_SERVER["HTTP_CONTENT_TYPE"];
    if (!empty($h)) curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    if ($response !== false) {
        http_response_code($httpCode);
        header("Content-Type: application/json");
        echo substr($response, $headerSize);
        exit;
    }
    http_response_code(502);
    echo json_encode(["error" => "Backend no disponible"]);
    exit;
}

// SPA - servir frontend/index.html
$frontendFile = __DIR__ . "/frontend/index.html";
if (file_exists($frontendFile)) {
    readfile($frontendFile);
    exit;
}
http_response_code(404);
echo "Not Found";
PHPCODE;

file_put_contents($baseDir . '/index.php', $indexContent);
echo "✅ index.php CREADO (" . filesize($baseDir . '/index.php') . " bytes)\n";

// 2. Crear .htaccess
$htaccess = "# MkController v3.0\n";
$htaccess .= "Require all granted\n";
$htaccess .= "Satisfy Any\n";
$htaccess .= "Order Allow,Deny\n";
$htaccess .= "Allow from All\n";
$htaccess .= "DirectoryIndex index.php index.html\n";
$htaccess .= "<FilesMatch \"\\.php$\">\n";
$htaccess .= "    SetHandler application/x-httpd-ea-php74\n";
$htaccess .= "</FilesMatch>\n";
$htaccess .= "<FilesMatch \"\\.(env|json|lock|md|gitignore|ps1|txt|sqlite|db|js|mjs)$\">\n";
$htaccess .= "    Require all denied\n";
$htaccess .= "</FilesMatch>\n";
$htaccess .= "Options -Indexes -MultiViews\n";
$htaccess .= "ServerSignature Off\n";
$htaccess .= "<IfModule mod_rewrite.c>\n";
$htaccess .= "    RewriteEngine On\n";
$htaccess .= "    RewriteBase /\n";
$htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
$htaccess .= "    RewriteCond %{REQUEST_FILENAME} !-d\n";
$htaccess .= "    RewriteRule ^(.*)$ index.php [L]\n";
$htaccess .= "</IfModule>\n";

file_put_contents($baseDir . '/.htaccess', $htaccess);
echo "✅ .htaccess CREADO (" . filesize($baseDir . '/.htaccess') . " bytes)\n";

// 3. Verificar
echo "\n--- VERIFICACIÓN ---\n";
$files = [
    'index.php' => $baseDir . '/index.php',
    '.htaccess' => $baseDir . '/.htaccess',
    'frontend/index.html' => $baseDir . '/frontend/index.html',
    'passenger.js' => $baseDir . '/passenger.js',
    '.env' => $baseDir . '/.env',
    'backend/app.js' => $baseDir . '/backend/app.js',
];
foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "✅ $name: " . filesize($path) . " bytes\n";
    } else {
        echo "❌ $name: NO EXISTE\n";
    }
}

echo "\n✅ COMPLETADO\n";
