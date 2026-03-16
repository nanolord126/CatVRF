<?php
// Быстро распаковать сжатые файлы с помощью preg_replace

$dir = new RecursiveDirectoryIterator(__DIR__ . '/app');
$iterator = new RecursiveIteratorIterator($dir);
$packed = [];

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        
        // Если файл сжат (много кода в одной строке)
        if (substr_count($content, "\n") < 5 && strlen($content) > 1000) {
            $packed[] = $file->getPathname();
        }
    }
}

echo count($packed) . " сжатых файлов найдено\n";

foreach (array_slice($packed, 0, 10) as $file) {
    echo $file . "\n";
}
