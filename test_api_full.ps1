#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

Write-Host "=== Testing MkController API ===" -ForegroundColor Cyan
Write-Host ""

# Test 1: Health endpoint
Write-Host "1. GET /api/health" -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/health" -Method Get -UseBasicParsing -TimeoutSec 15
    Write-Host "   Status: $($r.StatusCode)" -ForegroundColor Green
    Write-Host "   Content-Type: $($r.Headers['Content-Type'])"
    Write-Host "   Content: $($r.Content)"
} catch {
    Write-Host "   Status: $($_.Exception.Response.StatusCode.value__)" -ForegroundColor Red
    try {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $body = $reader.ReadToEnd()
        $reader.Close()
        Write-Host "   Body: $body"
    } catch {}
}

Write-Host ""

# Test 2: Login
Write-Host "2. POST /api/auth/login" -ForegroundColor Yellow
try {
    $body = '{"username":"admin","password":"admin123"}'
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/auth/login" -Method POST -Body $body -ContentType "application/json" -UseBasicParsing -TimeoutSec 15
    Write-Host "   Status: $($r.StatusCode)" -ForegroundColor Green
    $loginData = $r.Content | ConvertFrom-Json
    Write-Host "   Success: $($loginData.success)"
    Write-Host "   Token: $($loginData.data.token.Substring(0,50))..."
    Write-Host "   User: $($loginData.data.user.username) / $($loginData.data.user.role)"
    $script:token = $loginData.data.token
} catch {
    Write-Host "   Status: $($_.Exception.Response.StatusCode.value__)" -ForegroundColor Red
    try {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $body = $reader.ReadToEnd()
        $reader.Close()
        Write-Host "   Body: $body"
    } catch {}
}

Write-Host ""

# Test 3: Dashboard stats (requires token)
if ($script:token) {
    Write-Host "3. GET /api/dashboard/stats" -ForegroundColor Yellow
    try {
        $authHeaders = @{Authorization="Bearer $($script:token)"}
        $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/dashboard/stats" -Method Get -Headers $authHeaders -UseBasicParsing -TimeoutSec 15
        Write-Host "   Status: $($r.StatusCode)" -ForegroundColor Green
        Write-Host "   Content: $($r.Content.Substring(0, [Math]::Min(300, $r.Content.Length)))"
    } catch {
        Write-Host "   Status: $($_.Exception.Response.StatusCode.value__)" -ForegroundColor Red
        try {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $body = $reader.ReadToEnd()
            $reader.Close()
            Write-Host "   Body: $body"
        } catch {}
    }
    
    Write-Host ""
    
    # Test 4: Routers
    Write-Host "4. GET /api/routers" -ForegroundColor Yellow
    try {
        $authHeaders = @{Authorization="Bearer $($script:token)"}
        $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/routers" -Method Get -Headers $authHeaders -UseBasicParsing -TimeoutSec 15
        Write-Host "   Status: $($r.StatusCode)" -ForegroundColor Green
        Write-Host "   Content: $($r.Content.Substring(0, [Math]::Min(300, $r.Content.Length)))"
    } catch {
        Write-Host "   Status: $($_.Exception.Response.StatusCode.value__)" -ForegroundColor Red
        try {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $body = $reader.ReadToEnd()
            $reader.Close()
            Write-Host "   Body: $body"
        } catch {}
    }
    
    Write-Host ""
    
    # Test 5: Users
    Write-Host "5. GET /api/users" -ForegroundColor Yellow
    try {
        $authHeaders = @{Authorization="Bearer $($script:token)"}
        $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/users" -Method Get -Headers $authHeaders -UseBasicParsing -TimeoutSec 15
        Write-Host "   Status: $($r.StatusCode)" -ForegroundColor Green
        Write-Host "   Content: $($r.Content.Substring(0, [Math]::Min(300, $r.Content.Length)))"
    } catch {
        Write-Host "   Status: $($_.Exception.Response.StatusCode.value__)" -ForegroundColor Red
        try {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $body = $reader.ReadToEnd()
            $reader.Close()
            Write-Host "   Body: $body"
        } catch {}
    }
    
    Write-Host ""
    
    # Test 6: Clients
    Write-Host "6. GET /api/clients" -ForegroundColor Yellow
    try {
        $authHeaders = @{Authorization="Bearer $($script:token)"}
        $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/clients" -Method Get -Headers $authHeaders -UseBasicParsing -TimeoutSec 15
        Write-Host "   Status: $($r.StatusCode)" -ForegroundColor Green
        Write-Host "   Content: $($r.Content.Substring(0, [Math]::Min(300, $r.Content.Length)))"
    } catch {
        Write-Host "   Status: $($_.Exception.Response.StatusCode.value__)" -ForegroundColor Red
        try {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $body = $reader.ReadToEnd()
            $reader.Close()
            Write-Host "   Body: $body"
        } catch {}
    }
    
    Write-Host ""
    
    # Test 7: Hotspot
    Write-Host "7. GET /api/hotspot/tickets" -ForegroundColor Yellow
    try {
        $authHeaders = @{Authorization="Bearer $($script:token)"}
        $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/hotspot/tickets" -Method Get -Headers $authHeaders -UseBasicParsing -TimeoutSec 15
        Write-Host "   Status: $($r.StatusCode)" -ForegroundColor Green
        Write-Host "   Content: $($r.Content.Substring(0, [Math]::Min(300, $r.Content.Length)))"
    } catch {
        Write-Host "   Status: $($_.Exception.Response.StatusCode.value__)" -ForegroundColor Red
        try {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $body = $reader.ReadToEnd()
            $reader.Close()
            Write-Host "   Body: $body"
        } catch {}
    }
    
    Write-Host ""
    
    # Test 8: nexusMK health
    Write-Host "8. GET /api/nexusmk/health" -ForegroundColor Yellow
    try {
        $authHeaders = @{Authorization="Bearer $($script:token)"}
        $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/nexusmk/health" -Method Get -Headers $authHeaders -UseBasicParsing -TimeoutSec 15
        Write-Host "   Status: $($r.StatusCode)" -ForegroundColor Green
        Write-Host "   Content: $($r.Content.Substring(0, [Math]::Min(300, $r.Content.Length)))"
    } catch {
        Write-Host "   Status: $($_.Exception.Response.StatusCode.value__)" -ForegroundColor Red
        try {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $body = $reader.ReadToEnd()
            $reader.Close()
            Write-Host "   Body: $body"
        } catch {}
    }
}

Write-Host ""
Write-Host "=== Tests Complete ===" -ForegroundColor Green
