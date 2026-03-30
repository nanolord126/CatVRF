<?php
declare(strict_types=1);

$root = __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Domains';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

$changedFiles = 0;
$finalRemaining = 0;
$packageRemaining = 0;

foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }

    $updated = str_replace(
        ['final /**', '@package %NAMESPACE%'],
        ['/**', '@package App\\Domains'],
        $content,
        $replaceCount
    );

    if ($replaceCount > 0) {
        file_put_contents($path, $updated);
        $changedFiles++;
        $content = $updated;
    }

    $finalRemaining += substr_count($content, 'final /**');
    $packageRemaining += substr_count($content, '@package %NAMESPACE%');
}

echo 'CHANGED=' . $changedFiles . PHP_EOL;
echo 'REMAIN_FINAL=' . $finalRemaining . PHP_EOL;
echo 'REMAIN_PACKAGE=' . $packageRemaining . PHP_EOL;
