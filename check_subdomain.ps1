#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Diagnóstico Subdominio" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# 1. DomainInfo
# ============================================
Write-Host "[1] DomainInfo..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/execute/DomainInfo/domains_data?format=hash"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    Write-Host $r.Content -ForegroundColor Gray
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# 2. SubDomain - listar subdominios
# ============================================
Write-Host ""
Write-Host "[2] SubDomain..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/execute/SubDomain/listsubdomains"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    Write-Host $r.Content -ForegroundColor Gray
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# 3. AddonDomain - listar addon domains
# ============================================
Write-Host ""
Write-Host "[3] AddonDomain..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/execute/AddonDomain/listaddondomains"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    Write-Host $r.Content -ForegroundColor Gray
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# 4. Verificar si hay redirección
# ============================================
Write-Host ""
Write-Host "[4] Verificando redirecciones..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/execute/Redirection/list_redirections"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    Write-Host $r.Content -ForegroundColor Gray
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# 5. Verificar si hay ModSecurity bloqueando
# ============================================
Write-Host ""
Write-Host "[5] Verificando ModSecurity..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/execute/ModSecurity/list_domains"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    Write-Host $r.Content -ForegroundColor Gray
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# 6. LiteSpeed - ver si hay config
# ============================================
Write-Host ""
Write-Host "[6] Verificando si hay API de LiteSpeed..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/execute/LiteSpeed/list_domains"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    Write-Host $r.Content -ForegroundColor Gray
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Completado" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
