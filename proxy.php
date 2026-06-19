<?php
/**
 * Proxy PHP para MkController v3.0
 * 
 * Redirige peticiones /api/* al servidor Node.js
 * Inicia el servidor Node.js si no está corriendo
 */

// Configuración
$nodeBin = '/opt/alt/alt-nodejs20/root/usr/bin/node';
$npmBin = '/opt/alt/alt-nodejs20/root/usr/bin/npm';
$appDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$entryPoint = $appDir . '/start.js';
$nodePort = 3001;
$pidFile = $appDir . '/node.pid';
$logFile = $appDir . '/node.log';

// Función para iniciar Node.js
function startNodeServer() {
    global $nodeBin, $entryPoint, $nodePort, $pidFile, $logFile, $appDir;
    
    // Verificar si ya está corriendo
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        if ($pid && is_numeric($pid)) {
            // Verificar si el proceso existe
            $output = [];
            exec("ps -p $pid 2>&1", $output, $exitCode);
            if ($exitCode === 0) {
                return $pid; // Ya está corriendo
            }
        }
    }
    
    // Iniciar Node.js en segundo plano
    $cmd = "cd $appDir && PORT=$nodePort nohup $nodeBin $entryPoint > $logFile 2>&1 & echo $!";
    $output = [];
    exec($cmd, $output, $exitCode);
    
    if ($exitCode === 0 && !empty($output)) {
        $pid = trim($output[0]);
        file_put_contents($pidFile, $pid);
        
        // Esperar a que el servidor inicie
        $maxWait = 10;
        for ($i = 0; $i < $maxWait; $i++) {
            $ch = curl_init("http://127.0.0.1:$nodePort/api/health");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                return $pid;
            }
            sleep(1);
        }
        return $pid;
    }
    
    return null;
}

// Obtener la URI solicitada
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Si es una ruta /api/*, redirigir al servidor Node.js
if (strpos($requestUri, '/api/') === 0) {
    header('Content-Type: application/json');
    
    // Iniciar servidor Node.js si es necesario
    $pid = startNodeServer();
    
    if (!$pid) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error' => 'No se pudo iniciar el servidor Node.js',
            'message' => 'El servidor backend no está disponible'
        ]);
        exit;
    }
    
    // Redirigir la petición al servidor Node.js
    $nodeUrl = "http://127.0.0.1:$nodePort" . $requestUri;
    
    $ch = curl_init($nodeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    // Pasar headers
    $headers = [];
    foreach (getallheaders() as $name => $value) {
        if (strtolower($name) !== 'host' && strtolower($name) !== 'content-length') {
            $headers[] = "$name: $value";
        }
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Pasar método y body
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestMethod);
    if ($requestMethod === 'POST' || $requestMethod === 'PUT') {
        $body = file_get_contents('php://input');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    
    // Pasar cookies
    if (!empty($_SERVER['HTTP_COOKIE'])) {
        curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    http_response_code($httpCode);
    if ($contentType) {
        header("Content-Type: $contentType");
    }
    echo $response;
    exit;
}

// Si no es /api/, dejar que el .htaccess maneje la ruta
return false;
