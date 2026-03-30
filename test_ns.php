<?php
$content = file_get_contents('app/Services/FraudControlService.php');
if (preg_match('/^namespace\s+([a-zA-Z0-9_\\\\]+)\s*;/m', $content, $matches)) {
    echo 'Actual NS: ' . $matches[1] . PHP_EOL;
}
$path = realpath('app/Services/FraudControlService.php');
$relativePath = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $path);
$relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
$dirName = dirname($relativePath);
$dirName = str_replace('\\', '/', $dirName);
$parts = explode('/', $dirName);
array_shift($parts);
$expectedNs = 'App';
if (count($parts) > 0 && rtrim($parts[0], '.') !== '') {
    $expectedNs .= '\\' . implode('\\', $parts);
}
echo 'Expected NS: ' . $expectedNs . PHP_EOL;