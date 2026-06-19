<?php
echo "PHP_VERSION: " . phpversion() . "\n";
echo "SERVER_SOFTWARE: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "disable_functions: " . ini_get('disable_functions') . "\n";
echo "open_basedir: " . ini_get('open_basedir') . "\n";
echo "curl: " . (function_exists('curl_init') ? 'YES' : 'NO') . "\n";
echo "exec: " . (function_exists('exec') ? 'YES' : 'NO') . "\n";
echo "shell_exec: " . (function_exists('shell_exec') ? 'YES' : 'NO') . "\n";
echo "file_put_contents: " . (function_exists('file_put_contents') ? 'YES' : 'NO') . "\n";
echo "OK\n";
