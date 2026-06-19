<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$startJs = $baseDir . '/start.js';

$content = '// Entry point for MkController v3.0
// Compatible with Phusion Passenger (cPanel Node.js Selector)
// And direct execution (node start.js)

require(\'dotenv\').config({ path: \'./backend/.env\' });
const app = require(\'./backend/app\');
const db = require(\'./backend/models/database\');
const seed = require(\'./backend/seed\');
const config = require(\'./backend/config/config\');

/**
 * Initialize database and run seed
 */
async function initialize() {
  try {
    console.log(\'=== MkController v3.0.0 ===\');
    console.log(\'Administracion de Routers MikroTik\');
    console.log(\'\');

    // Initialize database
    console.log(\'[Init] Initializing database...\');
    await db.initialize();

    // Run seed (create initial data if not exists)
    console.log(\'[Init] Checking initial data...\');
    await seed();

    console.log(\'[Init] Initialization completed successfully\');
    console.log(\'[Init] MkController ready to serve requests\');
  } catch (error) {
    console.error(\'[Init] Fatal error:\', error);
    process.exit(1);
  }
}

// Initialize
initialize();

// If executed directly (not by Passenger), start HTTP server
if (!process.env.PASSENGER_APP) {
  const PORT = process.env.PORT || config.server.port || 3000;
  app.listen(PORT, \'127.0.0.1\', () => {
    console.log(\'[Server] Server started on port \' + PORT);
  });
}

// Export the Express app for Passenger
module.exports = app;
';

$result = file_put_contents($startJs, $content);
if ($result !== false) {
    echo "OK: start.js actualizado ($result bytes)\n";
} else {
    echo "ERROR: No se pudo escribir start.js\n";
}

// Also update proxy.php to ensure it has the right content
$proxyPhp = $baseDir . '/proxy.php';
$proxyContent = '<?php
/**
 * Proxy PHP para MkController v3.0
 * 
 * Redirige peticiones /api/* al servidor Node.js
 * Inicia el servidor Node.js si no esta corriendo
 */

// Configuracion
$nodeBin = \'/opt/alt/alt-nodejs20/root/usr/bin/node\';
$appDir = \'/home/nexusyl/nexusmk.nexussolutionsyl.com\';
$entryPoint = $appDir . \'/start.js\';
$nodePort = 3001;
$pidFile = $appDir . \'/node.pid\';
$logFile = $appDir . \'/node.log\';

// Funcion para iniciar Node.js
function startNodeServer() {
    global $nodeBin, $entryPoint, $nodePort, $pidFile, $logFile, $appDir;
    
    // Verificar si ya esta corriendo
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        if ($pid && is_numeric($pid)) {
            // Verificar si el proceso existe
            $output = [];
            exec("ps -p $pid 2>&1", $output, $exitCode);
            if ($exitCode === 0) {
                return $pid;
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
$requestUri = $_SERVER[\'REQUEST_URI\'];
$requestMethod = $_SERVER[\'REQUEST_METHOD\'];

// Si es una ruta /api/*, redirigir al servidor Node.js
if (strpos($requestUri, \'/api/\') === 0) {
    header(\'Content-Type: application/json\');
    
    // Iniciar servidor Node.js si es necesario
    $pid = startNodeServer();
    
    if (!$pid) {
        http_response_code(503);
        echo json_encode([
            \'success\' => false,
            \'error\' => \'No se pudo iniciar el servidor Node.js\',
            \'message\' => \'El servidor backend no esta disponible\'
        ]);
        exit;
    }
    
    // Redirigir la peticion al servidor Node.js
    $nodeUrl = "http://127.0.0.1:$nodePort" . $requestUri;
    
    $ch = curl_init($nodeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    // Pasar headers
    $headers = [];
    foreach (getallheaders() as $name => $value) {
        if (strtolower($name) !== \'host\' && strtolower($name) !== \'content-length\') {
            $headers[] = "$name: $value";
        }
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Pasar metodo y body
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestMethod);
    if ($requestMethod === \'POST\' || $requestMethod === \'PUT\') {
        $body = file_get_contents(\'php://input\');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    
    // Pasar cookies
    if (!empty($_SERVER[\'HTTP_COOKIE\'])) {
        curl_setopt($ch, CURLOPT_COOKIE, $_SERVER[\'HTTP_COOKIE\']);
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
';

file_put_contents($proxyPhp, $proxyContent);
echo "proxy.php actualizado\n";

// Now kill old node and start fresh
echo "\n--- Starting Node.js ---\n";
exec("pkill -f \"$entryPoint\" 2>/dev/null");
if (file_exists($pidFile)) unlink($pidFile);

$cmd = "cd $appDir && PORT=$nodePort nohup $nodeBin $entryPoint > $logFile 2>&1 & echo \$!";
exec($cmd, $output, $exitCode);
echo "Exit code: $exitCode\n";
if (!empty($output)) {
    $pid = trim($output[0]);
    file_put_contents($pidFile, $pid);
    echo "PID: $pid\n";
    
    sleep(3);
    
    // Test
    $ch = curl_init("http://127.0.0.1:$nodePort/api/health");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "Health check: HTTP $httpCode\n";
    echo "Response: " . ($result ? substr($result, 0, 500) : "(empty)") . "\n";
    if ($error) echo "CURL Error: $error\n";
    
    if (file_exists($logFile)) {
        echo "\nNode log:\n";
        echo file_get_contents($logFile);
    }
} else {
    echo "FAILED to start Node.js\n";
}

echo "\nCOMPLETADO\n";
