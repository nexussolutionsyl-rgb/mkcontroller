<?php
// Script temporal para descomprimir el proyecto MkController
$zipFile = '/home/nexusyl/nexusmk.nexussolutionsyl.com/mkcontroller.zip';
$destDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

if (!file_exists($zipFile)) {
    die("ERROR: ZIP file not found at $zipFile");
}

$zip = new ZipArchive();
if ($zip->open($zipFile) !== TRUE) {
    die("ERROR: Could not open ZIP file");
}

$zip->extractTo($destDir);
$zip->close();

echo "SUCCESS: Files extracted to $destDir\n";

// Listar archivos extraídos
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($destDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$count = 0;
foreach ($files as $file) {
    if ($file->isFile()) {
        $count++;
    }
}

echo "Total files extracted: $count\n";

// Eliminar este script después de ejecutarse
// unlink(__FILE__);
// echo "Cleanup: Script deleted\n";
?>
