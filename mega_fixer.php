<?php
declare(strict_types=1);

/**
 * MEGA FIXER 2026 - исправляет Class$this->prop -> ClassProp во всех PHP файлах
 */

$dirs = [
    __DIR__ . '/app',
    __DIR__ . '/modules',
    __DIR__ . '/routes',
];

$totalFixed = 0;
$filesFixed = [];

foreach ($dirs as $baseDir) {
    if (!is_dir($baseDir)) continue;
    
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
    
    foreach ($it as $file) {
        if ($file->isDir() || $file->getExtension() !== 'php') {
            continue;
        }
        
        $path = $file->getPathname();
        $original = file_get_contents($path);
        $content = $original;
        
        // Pattern: SomeClass$this->propertyName -> SomeClassPropertyName
        // e.g., Tutor$this->session -> TutorSession
        // e.g., Class$this->session -> ClassSession
        $content = preg_replace_callback(
            '/([A-Z][A-Za-z0-9_]*)\$this->([a-z][A-Za-z0-9_]*)/',
            function ($matches) {
                $prefix = $matches[1];
                $suffix = ucfirst($matches[2]);
                return $prefix . $suffix;
            },
            $content
        );
        
        if ($content !== $original) {
            file_put_contents($path, $content);
            $filesFixed[] = $path;
            $totalFixed++;
        }
    }
}

echo "Fixed $totalFixed files:\n";
foreach ($filesFixed as $f) {
    echo "  - $f\n";
}
echo "\nDone!\n";
