<?php
/**
 * Prueba el health endpoint de nexusMK desde localhost
 * para ver el mensaje de error completo
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST NEXUSMK HEALTH ===\n\n";

$url = 'http://127.0.0.1:3001/api/nexusmk/health';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_HTTPHEADER => ['Accept: application/json']
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "cURL Error: " . ($error ?: 'ninguno') . "\n";
echo "Response:\n";
echo $response ?: '(vacia)' . "\n";

echo "\n\n=== TEST API PRINCIPAL HEALTH ===\n\n";

$url2 = 'http://127.0.0.1:3001/api/health';
$ch2 = curl_init($url2);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 3
]);
$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "HTTP Status: $httpCode2\n";
echo "Response:\n";
echo $response2 ?: '(vacia)' . "\n";

echo "\n=== FIN ===\n";
