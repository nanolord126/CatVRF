<?php
declare(strict_types=1);

$dir = 'database/migrations';
$files = array_filter(scandir($dir), fn($f) => str_ends_with($f, '.php'));
$errors = [];

foreach ($files as $file) {
    $path = $dir . '/' . $file;
    $result = shell_exec('php -l ' . escapeshellarg($path) . ' 2>&1');
    if (strpos($result, 'No syntax errors') === false) {
        $errors[] = $file . ': ' . trim($result);
    }
}

if (empty($errors)) {
    echo 'Checked: ' . count($files) . ' migrations' . PHP_EOL;
    echo 'Errors: 0' . PHP_EOL;
    echo '✅ All migrations passed PHP syntax validation' . PHP_EOL;
} else {
    echo 'Checked: ' . count($files) . ' migrations' . PHP_EOL;
    echo 'Errors: ' . count($errors) . PHP_EOL . PHP_EOL;
    foreach ($errors as $error) {
        echo '⚠️  ' . $error . PHP_EOL;
    }
}
