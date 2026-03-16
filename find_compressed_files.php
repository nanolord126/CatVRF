<?php
$dir = __DIR__ . '/app';
$compressed = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        
        // Признаки сжатого файла:
        // 1. Начинается с <?php но весь код в одной строке
        // 2. Много пробелов подряд внутри кода вместо новых строк
        
        if (preg_match('/^<\?php\s+[^{]*(?:declare|namespace|use|class|trait|interface).*\s{2,}(?:namespace|use|class|trait|interface)/', $content)) {
            $compressed[] = $file->getPathname();
        } elseif (substr_count($content, "\n") < 10 && strlen($content) > 2000) {
            // Если очень мало новых строк но много кода - вероятно сжато
            $compressed[] = $file->getPathname();
        }
    }
}

foreach ($compressed as $file) {
    $relative = str_replace(__DIR__ . '/', '', $file);
    echo "$relative\n";
}

echo "\nTotal compressed files: " . count($compressed) . "\n";
