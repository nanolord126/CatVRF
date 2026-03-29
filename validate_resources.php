<?php
declare(strict_types=1);

$errors = [];
$checked = 0;
$resourcesDir = __DIR__ . '/app/Filament/Tenant/Resources';

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($resourcesDir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $file) {
    if (strpos($file->getFilename(), 'Resource.php') !== false) {
        $checked++;
        $result = shell_exec('php -l ' . escapeshellarg($file->getRealPath()) . ' 2>&1');
        if (strpos($result, 'Parse error') !== false || strpos($result, 'Syntax error') !== false) {
            $errors[] = [
                'file' => str_replace($resourcesDir . '/', '', $file->getRealPath()),
                'error' => trim($result)
            ];
        }
    }
}

echo "Checked: $checked files\n";
echo "Errors: " . count($errors) . "\n\n";

if (count($errors) > 0) {
    echo "=== SYNTAX ERRORS ===\n";
    foreach ($errors as $err) {
        echo $err['file'] . ":\n";
        echo "  " . $err['error'] . "\n\n";
    }
} else {
    echo "✅ All Filament Resources passed PHP syntax validation\n";
}
