#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Fix Root 403 - Estrategia múltiple" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# 1. Verificar si podemos usar API v2 para modificar dominio
# ============================================
Write-Host "[1] Verificando APIs disponibles..." -ForegroundColor Yellow
$apis = @(
    "SubDomain/listsubdomains",
    "Park/listparked",
    "Mime/list_types",
    "LangPHP/php_get_impacted_domains",
    "SSL/list_certs",
    "UserManager/list_users"
)
foreach ($api in $apis) {
    try {
        $url = "https://server166.web-hosting.com:2083/execute/$api"
        $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
        $status = ($r.Content -match '"status":1') ? "OK" : "FAIL"
        Write-Host "  $api -> $status" -ForegroundColor Gray
    } catch {
        Write-Host "  $api -> ERROR" -ForegroundColor DarkGray
    }
}

# ============================================
# 2. Intentar crear un redirect desde la raíz a /frontend/index.html
# usando PHP directamente
# ============================================
Write-Host ""
Write-Host "[2] Creando index.php en la raíz vía PHP directo..." -ForegroundColor Yellow

# Primero, crear un PHP simple que sirva como index
$simpleIndex = '<?php
// MkController v3.0 - Entry Point
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
    $headers2 = [];
    if (isset($_SERVER["HTTP_AUTHORIZATION"])) $headers2[] = "Authorization: " . $_SERVER["HTTP_AUTHORIZATION"];
    if (isset($_SERVER["HTTP_CONTENT_TYPE"])) $headers2[] = "Content-Type: " . $_SERVER["HTTP_CONTENT_TYPE"];
    if (!empty($headers2)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers2);
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
';

# Crear script PHP que escriba el index.php
$creatorScript = '<?php
$baseDir = "/home/nexusyl/nexusmk.nexussolutionsyl.com";

// 1. Crear index.php
$indexContent = ' . var_export($simpleIndex, true) . ';
file_put_contents($baseDir . "/index.php", $indexContent);
echo "✅ index.php creado: " . filesize($baseDir . "/index.php") . " bytes\n";

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

file_put_contents($baseDir . "/.htaccess", $htaccess);
echo "✅ .htaccess creado: " . filesize($baseDir . "/.htaccess") . " bytes\n";

// 3. Verificar
echo "\n--- VERIFICACION ---\n";
echo "index.php: " . (file_exists($baseDir . "/index.php") ? "SI" : "NO") . "\n";
echo ".htaccess: " . (file_exists($baseDir . "/.htaccess") ? "SI" : "NO") . "\n";
echo "frontend/index.html: " . (file_exists($baseDir . "/frontend/index.html") ? "SI" : "NO") . "\n";
echo "\n✅ COMPLETADO\n";
';

# Guardar el script creador
$creatorPath = "create_root_files.php"
[System.IO.File]::WriteAllText((Resolve-Path $creatorPath), $creatorScript, [System.Text.Encoding]::UTF8)

Write-Host "  Script creado: $creatorPath" -ForegroundColor Green

# ============================================
# 3. Subir y ejecutar
# ============================================
Write-Host ""
Write-Host "[3] Subiendo create_root_files.php..." -ForegroundColor Yellow

try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=trash&sourcefiles=$remoteDir/create_root_files.php"
    Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15 | Out-Null
} catch {}

try {
    $localFile = "create_root_files.php"
    $fileBytes = [System.IO.File]::ReadAllBytes((Resolve-Path $localFile))
    $fileContent = [System.Text.Encoding]::Default.GetString($fileBytes)
    
    $boundary = [Guid]::NewGuid().ToString("N")
    $lf = "`r`n"
    
    $bodyLines = @()
    $bodyLines += "--$boundary"
    $bodyLines += "Content-Disposition: form-data; name=`"file-0`"; filename=`"create_root_files.php`""
    $bodyLines += "Content-Type: application/x-php"
    $bodyLines += ""
    $bodyLines += $fileContent
    $bodyLines += "--$boundary--"
    
    $bodyString = $bodyLines -join $lf
    
    $uploadUrl = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir"
    
    $r = Invoke-WebRequest -Uri $uploadUrl -Headers $headers -Method POST -Body $bodyString -ContentType "multipart/form-data; boundary=$boundary" -UseBasicParsing -TimeoutSec 30
    Write-Host "  Upload: $($r.Content)" -ForegroundColor Gray
    if ($r.Content -match '"result":1') {
        Write-Host "  ✅ Subido!" -ForegroundColor Green
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# 4. Ejecutar
# ============================================
Write-Host ""
Write-Host "[4] Ejecutando create_root_files.php..." -ForegroundColor Yellow
Start-Sleep -Seconds 3
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/create_root_files.php" -UseBasicParsing -TimeoutSec 30
    Write-Host "  Response:" -ForegroundColor White
    Write-Host $r.Content -ForegroundColor Green
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# 5. Probar
# ============================================
Write-Host ""
Write-Host "[5] Probando acceso..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

$testUrls = @(
    @{Url="https://nexusmk.nexussolutionsyl.com/"; Desc="Raíz"},
    @{Url="https://nexusmk.nexussolutionsyl.com/index.php"; Desc="index.php"},
    @{Url="https://nexusmk.nexussolutionsyl.com/frontend/index.html"; Desc="index.html"},
    @{Url="https://nexusmk.nexussolutionsyl.com/api/health"; Desc="API health"}
)

foreach ($test in $testUrls) {
    try {
        $r = Invoke-WebRequest -Uri $test.Url -UseBasicParsing -TimeoutSec 10
        $preview = ""
        if ($r.Content.Length -gt 0) {
            $preview = $r.Content.Substring(0, [Math]::Min(150, $r.Content.Length))
        }
        Write-Host "  $($test.Desc): $($r.StatusCode) ($($r.Content.Length) bytes)" -ForegroundColor Green
        if ($preview) { Write-Host "    -> $preview" -ForegroundColor Gray }
    } catch {
        $code = $_.Exception.Response.StatusCode.value__
        if ($code -eq 403) {
            Write-Host "  $($test.Desc): $code (denegado)" -ForegroundColor Yellow
        } else {
            Write-Host "  $($test.Desc): $code" -ForegroundColor Red
        }
    }
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Completado" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
