$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Check Status"
Write-Host "============================================"
Write-Host ""

# Verificar node_modules
Write-Host "[1] Verificando node_modules..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/list_files?dir=$remoteDir/backend"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        $hasNM = $false
        $hasEnv = $false
        foreach ($item in $result.data) {
            if ($item.file -eq 'node_modules') { $hasNM = $true }
            if ($item.file -eq '.env') { $hasEnv = $true }
        }
        Write-Host "  node_modules: $(if($hasNM){'✅'}else{'❌'})"
        Write-Host "  .env: $(if($hasEnv){'✅'}else{'❌'})"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# Verificar app registrada
Write-Host "[2] Verificando app Passenger..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host "  $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# Probar la web
Write-Host "[3] Probando https://nexusmk.nexussolutionsyl.com..."
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com" -UseBasicParsing -TimeoutSec 15
    Write-Host "  Status: $($r.StatusCode)"
    if ($r.Content.Length -gt 0) {
        $preview = $r.Content.Substring(0, [Math]::Min(200, $r.Content.Length))
        Write-Host "  Preview: $preview"
    }
} catch {
    Write-Host "  Status: $($_.Exception.Message)"
}

# Probar API health
Write-Host "[4] Probando /api/health..."
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/health" -UseBasicParsing -TimeoutSec 15
    Write-Host "  Status: $($r.StatusCode) - $($r.Content)"
} catch {
    Write-Host "  Status: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "============================================"
