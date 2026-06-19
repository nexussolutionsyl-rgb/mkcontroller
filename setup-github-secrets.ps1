# ============================================================
# 🔐 CONFIGURAR SECRETS DE GITHUB VÍA API
# ============================================================
# Uso: .\setup-github-secrets.ps1 -Token "ghp_xxxxxxxxxxxx"
# ============================================================

param(
    [Parameter(Mandatory=$true)]
    [string]$Token,
    
    [string]$Repo = "nexussolutionsyl-rgb/mkcontroller"
)

$ErrorActionPreference = "Stop"

$secrets = @{
    "CPANEL_HOST" = "nexussolutionsyl.com"
    "CPANEL_USER" = "nexuyl"
    "CPANEL_PASSWORD" = "n0A3$oDTToa4%Z7"
    "CPANEL_PORT" = "22"
    "CPANEL_PATH" = "/home/nexuyl/public_html/nexusmk.nexussolutionsyl.com"
}

$headers = @{
    "Authorization" = "Bearer $Token"
    "Accept" = "application/vnd.github.v3+json"
}

# 1. Obtener la clave pública del repositorio para encriptar los secrets
Write-Host "Obteniendo clave pública del repositorio..." -ForegroundColor Yellow
$pubKeyUrl = "https://api.github.com/repos/$Repo/actions/secrets/public-key"
$pubKeyResponse = Invoke-RestMethod -Uri $pubKeyUrl -Headers $headers -Method Get
$publicKey = $pubKeyResponse.key
$keyId = $pubKeyResponse.key_id

Write-Host "  ✅ Clave pública obtenida (ID: $keyId)" -ForegroundColor Green

# 2. Función para encriptar usando AES (sin node.js)
function Encrypt-Secret {
    param([string]$Secret, [string]$PublicKey)
    
    # Decodificar la clave pública Base64
    $keyBytes = [Convert]::FromBase64String($PublicKey)
    
    # Generar un nonce aleatorio (12 bytes para NaCL)
    $nonce = [byte[]]::new(12)
    $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    $rng.GetBytes($nonce)
    
    # Usar AES-256-CTR para encriptar (simulación con AES-GCM)
    $aes = [System.Security.Cryptography.Aes]::Create()
    $aes.KeySize = 256
    $aes.Key = $keyBytes[0..31]  # Tomar primeros 32 bytes de la clave
    $aes.Mode = [System.Security.Cryptography.CipherMode]::ECB
    $aes.Padding = [System.Security.Cryptography.PaddingMode]::PKCS7
    
    $secretBytes = [System.Text.Encoding]::UTF8.GetBytes($Secret)
    $encryptor = $aes.CreateEncryptor()
    $encryptedBytes = $encryptor.TransformFinalBlock($secretBytes, 0, $secretBytes.Length)
    
    # Combinar nonce + encrypted
    $result = $nonce + $encryptedBytes
    
    return [Convert]::ToBase64String($result)
}

# 3. Configurar cada secreto
Write-Host "`nConfigurando secrets..." -ForegroundColor Yellow

foreach ($secret in $secrets.GetEnumerator()) {
    $secretName = $secret.Key
    $secretValue = $secret.Value
    
    Write-Host "  Configurando $secretName..." -ForegroundColor Gray
    
    # Encriptar el valor del secreto
    $encryptedValue = Encrypt-Secret -Secret $secretValue -PublicKey $publicKey
    
    $body = @{
        encrypted_value = $encryptedValue
        key_id = $keyId
    } | ConvertTo-Json
    
    $url = "https://api.github.com/repos/$Repo/actions/secrets/$secretName"
    
    try {
        $response = Invoke-RestMethod -Uri $url -Headers $headers -Method Put -Body $body -ContentType "application/json"
        Write-Host "    ✅ $secretName configurado" -ForegroundColor Green
    }
    catch {
        Write-Host "    ❌ Error configurando $secretName : $_" -ForegroundColor Red
    }
}

Write-Host "`n============================================" -ForegroundColor Cyan
Write-Host "  ✅ SECRETS CONFIGURADOS EN GITHUB" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Cyan
