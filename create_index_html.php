<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

// Crear index.html en la raiz que redirija a frontend/index.html
$indexHtml = '<!DOCTYPE html>
<html>
<head>
<meta http-equiv="refresh" content="0; url=frontend/index.html">
<title>Redirigiendo...</title>
</head>
<body>
<script>window.location.href="frontend/index.html";</script>
<p>Redirigiendo a <a href="frontend/index.html">frontend/index.html</a>...</p>
</body>
</html>';

$result = file_put_contents($baseDir . '/index.html', $indexHtml);
if ($result !== false) {
    echo "index.html creado ($result bytes)\n";
} else {
    echo "ERROR: No se pudo crear index.html\n";
}

// Verificar que existe
if (file_exists($baseDir . '/index.html')) {
    echo "index.html verificado\n";
    echo "Contenido:\n" . file_get_contents($baseDir . '/index.html') . "\n";
}

// Verificar index.php tambien
if (file_exists($baseDir . '/index.php')) {
    echo "index.php existe\n";
    echo "Contenido:\n" . file_get_contents($baseDir . '/index.php') . "\n";
}

echo "\nCOMPLETADO\n";
