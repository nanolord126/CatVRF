<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('C:/opt/kotvrf/CatVRF'));
foreach ($files as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') continue;
    $path = str_replace('\\', '/', $file->getPathname());
    if (str_contains($path, '/vendor/') || str_contains($path, '/storage/') || str_contains($path, '/bootstrap/cache/')) continue;
    
    $f = fopen($path, 'r');
    if (!$f) continue;
    $start = fread($f, 5);
    fclose($f);
    
    if ($start !== '<?php') {
        $content = file_get_contents($path);
        if (str_starts_with(trim($content), 'declare') || str_starts_with(trim($content), 'namespace')) {
            file_put_contents($path, '<?php' . "\n" . ltrim($content));
            echo 'Fixed: ' . $path . "\n";
        }
    }
}
