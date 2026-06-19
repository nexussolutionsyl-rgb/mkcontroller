<?php
/**
 * Prueba el endpoint /api/nexusmk/dispositivos desde localhost
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST NEXUSMK DISPOSITIVOS ===\n\n";

// Primero obtener token
$loginUrl = 'http://127.0.0.1:3001/api/auth/login';
$loginData = json_encode(['username' => 'admin', 'password' => 'admin123']);

$ch = curl_init($loginUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $loginData,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 10
]);
$loginResp = curl_exec($ch);
$loginHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login HTTP: $loginHttp\n";
$loginData = json_decode($loginResp, true);
$token = $loginData['data']['token'] ?? '';
echo "Token: " . substr($token, 0, 30) . "...\n\n";

if (!$token) {
    echo "ERROR: No se pudo obtener token\n";
    exit;
}

// Probar dispositivos
$url = 'http://127.0.0.1:3001/api/nexusmk/dispositivos';
$ch2 = curl_init($url);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Accept: application/json'],
    CURLOPT_TIMEOUT => 15
]);
$response = curl_exec($ch2);
$httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$error = curl_error($ch2);
curl_close($ch2);

echo "HTTP Status: $httpCode\n";
echo "cURL Error: " . ($error ?: 'ninguno') . "\n";
echo "Response:\n";
echo $response ?: '(vacia)' . "\n";

echo "\n=== FIN ===\n";
