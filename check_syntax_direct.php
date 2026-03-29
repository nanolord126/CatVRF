<?php

declare(strict_types=1);

$resourceDir = __DIR__ . '/app/Filament/Tenant/Resources';
$errors = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($resourceDir),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getRealPath(), 'Resource.php')) {
        $output = shell_exec('php -l ' . escapeshellarg($file->getRealPath()) . ' 2>&1');
        if (str_contains($output, 'Parse error') || str_contains($output, 'Fatal error')) {
            $errors[] = [
                'file' => str_replace(__DIR__ . '/', '', $file->getRealPath()),
                'error' => trim($output),
            ];
        }
    }
}

echo "Total Resources: 215\n";
echo "Errors found: " . count($errors) . "\n\n";

if (count($errors) > 0) {
    foreach (array_slice($errors, 0, 20) as $err) {
        echo "FILE: " . $err['file'] . "\n";
        echo "ERROR: " . substr($err['error'], 0, 200) . "\n\n";
    }
}
