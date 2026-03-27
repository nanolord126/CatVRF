<?php

declare(strict_types=1);

$dir = __DIR__ . '/app/Domains';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$count = 0;
foreach ($it as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') {
        continue;
    }

    $content = file_get_contents($file->getPathname());
    
    // Pattern: SomeClass$this->property
    $pattern = '/([\\\\][A-Z][a-zA-Z0-9_]*)\$this->([a-z][a-zA-Z0-9_]*)/';
    
    if (preg_match($pattern, $content)) {
        echo "Fixing {$file->getPathname()}\n";
        
        // Check for specific SportClass case first if it matches
        if (strpos($file->getPathname(), 'Sports') !== false) {
             $content = str_replace('\App\Domains\Sports\Models\Class$this->session', '\App\Domains\Sports\Models\ClassSession', $content);
        }

        $newContent = preg_replace($pattern, '$1::$2', $content);
        file_put_contents($file->getPathname(), $newContent);
        $count++;
    }
}

echo "Fixed $count files with class property corruption.\n";
