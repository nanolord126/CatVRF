<?php
declare(strict_types=1);

$minFiles = [];
$iterator = new RecursiveDirectoryIterator('app/Filament/Tenant/Resources');
$recursiveIterator = new RecursiveIteratorIterator($iterator);
$regex = new RegexIterator($recursiveIterator, '/.*Resource\.php$/');

foreach ($regex as $file) {
    $path = $file->getPathname();
    if (strpos($path, 'Pages') !== false) continue;
    
    $lines = substr_count(file_get_contents($path), "\n");
    if ($lines < 100 && $lines > 30) {
        $minFiles[] = [$file->getBasename(), $lines, $path];
    }
}

usort($minFiles, fn($a, $b) => $b[1] <=> $a[1]);

echo "Файлов < 100 строк: " . count($minFiles) . "\n";
echo "---\n";
foreach (array_slice($minFiles, 0, 15) as $f) {
    echo $f[0] . " (" . $f[1] . " lines)\n";
}
