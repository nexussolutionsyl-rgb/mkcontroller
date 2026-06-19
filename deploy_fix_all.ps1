#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cpanelUser = "nexusyl"
$cpanelPass = "n0A3$oDTToa4%Z7"
$baseUrl = "https://server166.web-hosting.com:2083"
$localZip = "fix_all.zip"
$localPhp = "fix_all.php"

Write-Host "=== Subir y ejecutar fix_all.php ===" -ForegroundColor Cyan

# 1. Crear ZIP con el script PHP
Write-Host "`n[1/3] Creando ZIP..." -ForegroundColor Yellow
if (Test-Path $localZip) { Remove-Item $localZip -Force }
Compress-Archive -Path $localPhp -DestinationPath $localZip -Force
Write-Host "  ZIP creado: $localZip" -ForegroundColor Green

# 2. Subir ZIP via Fileman.uploadfiles
Write-Host "`n[2/3] Subiendo ZIP al servidor..." -ForegroundColor Yellow
$uploadUrl = "$baseUrl/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2"

$uploadParams = @{
    Uri = $uploadUrl
    Method = "POST"
    Credential = New-Object System.Management.Automation.PSCredential($cpanelUser, (ConvertTo-SecureString $cpanelPass -AsPlainText -Force))
    InFile = $localZip
    ContentType = "application/zip"
    Headers = @{
        "Content-Disposition" = 'form-data; name="file-0"; filename="fix_all.php"'
    }
}

try {
    $uploadResult = Invoke-WebRequest @uploadParams -UseBasicParsing
    Write-Host "  ZIP subido exitosamente!" -ForegroundColor Green
    Write-Host "  Response: $($uploadResult.Content)" -ForegroundColor Gray
} catch {
    Write-Host "  Error subiendo ZIP: $_" -ForegroundColor Red
    
    # Intentar con multipart/form-data manual
    Write-Host "  Intentando con multipart/form-data manual..." -ForegroundColor Yellow
    
    $boundary = "----Boundary" + [Guid]::NewGuid().ToString("N")
    $bodyLines = @()
    $bodyLines += "--$boundary"
    $bodyLines += 'Content-Disposition: form-data; name="file-0"; filename="fix_all.php"'
    $bodyLines += "Content-Type: application/x-php"
    $bodyLines += ""
    $bodyLines += [System.IO.File]::ReadAllText((Resolve-Path $localPhp))
    $bodyLines += "--$boundary--"
    $bodyBytes = [System.Text.Encoding]::UTF8.GetBytes($bodyLines -join "`r`n")
    
    try {
        $uploadResult = Invoke-WebRequest -Uri $uploadUrl -Method POST -Credential (New-Object System.Management.Automation.PSCredential($cpanelUser, (ConvertTo-SecureString $cpanelPass -AsPlainText -Force))) -ContentType "multipart/form-data; boundary=$boundary" -Body $bodyBytes -UseBasicParsing
        Write-Host "  ZIP subido exitosamente (multipart)!" -ForegroundColor Green
        Write-Host "  Response: $($uploadResult.Content)" -ForegroundColor Gray
    } catch {
        Write-Host "  Error en multipart: $_" -ForegroundColor Red
        
        # Último intento: subir como texto plano
        Write-Host "  Intentando subir como texto plano..." -ForegroundColor Yellow
        $phpContent = [System.IO.File]::ReadAllText((Resolve-Path $localPhp))
        $encoded = [System.Web.HttpUtility]::UrlEncode($phpContent)
        
        $saveUrl = "$baseUrl/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2"
        $saveParams = @{
            Uri = $saveUrl
            Method = "POST"
            Credential = New-Object System.Management.Automation.PSCredential($cpanelUser, (ConvertTo-SecureString $cpanelPass -AsPlainText -Force))
            Body = "dir=%2Fhome%2Fnexusyl%2Fnexusmk.nexussolutionsyl.com&file=fix_all.php&content=$encoded"
            ContentType = "application/x-www-form-urlencoded"
            UseBasicParsing = $true
        }
        
        try {
            $saveResult = Invoke-WebRequest @saveParams
            Write-Host "  Script subido como texto plano!" -ForegroundColor Green
            Write-Host "  Response: $($saveResult.Content)" -ForegroundColor Gray
        } catch {
            Write-Host "  Error en savefile: $_" -ForegroundColor Red
            exit 1
        }
    }
}

# 3. Ejecutar el script PHP via HTTP
Write-Host "`n[3/3] Ejecutando fix_all.php..." -ForegroundColor Yellow
$execUrl = "https://nexusmk.nexussolutionsyl.com/fix_all.php"

try {
    $execResult = Invoke-WebRequest -Uri $execUrl -UseBasicParsing -TimeoutSec 30
    Write-Host "  Script ejecutado!" -ForegroundColor Green
    Write-Host "`n=== RESULTADO ===" -ForegroundColor Cyan
    Write-Host $execResult.Content -ForegroundColor White
} catch {
    Write-Host "  Error ejecutando script: $_" -ForegroundColor Red
    Write-Host "  (Esto es normal si el .htaccess no permite PHP aún)" -ForegroundColor Yellow
}

# 4. Limpiar
Write-Host "`n[Cleanup] Eliminando ZIP local..." -ForegroundColor Gray
if (Test-Path $localZip) { Remove-Item $localZip -Force }

Write-Host "`n=== Proceso completado ===" -ForegroundColor Cyan
