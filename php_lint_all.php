<?php
declare(strict_types=1);

/**
 * php_lint_all.php — проверка синтаксиса всех PHP файлов в проекте
 */

$baseDir = __DIR__;
$dirs = ['app', 'modules', 'routes'];
$errors = [];
$scanned = 0;

foreach ($dirs as $dir) {
    $fullDir = $baseDir . DIRECTORY_SEPARATOR . $dir;
    if (!is_dir($fullDir)) continue;

    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iter as $file) {
        if ($file->getExtension() !== 'php') continue;
        $scanned++;

        $out = shell_exec('php -l ' . escapeshellarg($file->getPathname()) . ' 2>&1');
        if ($out === null || strpos($out, 'No syntax errors') === false) {
            $errors[] = [
                'file' => $file->getPathname(),
                'msg'  => trim((string)$out),
            ];
        }
    }
}

echo "Scanned: $scanned files\n";
echo "Syntax errors: " . count($errors) . " files\n\n";
foreach ($errors as $e) {
    echo "FILE: {$e['file']}\n";
    echo "  {$e['msg']}\n\n";
}
