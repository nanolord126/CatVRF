<?php

$dir = __DIR__ . '/app/Events';
$files = glob("$dir/*.php");
$fixed = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    // Check if file uses Log:: but doesn't have the import
    if (strpos($content, 'Log::') !== false && 
        strpos($content, 'use Illuminate\Support\Facades\Log;') === false) {
        
        // Add Log import after namespace
        $content = preg_replace(
            '/(namespace [^;]+;)/',
            "$1\nuse Illuminate\Support\Facades\Log;",
            $content
        );
        
        if ($content !== $original) {
            file_put_contents($file, $content);
            echo basename($file) . " - Added Log import\n";
            $fixed++;
        }
    }
}

echo "\nFixed: $fixed files\n";
