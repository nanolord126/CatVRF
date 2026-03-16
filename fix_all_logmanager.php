<?php

$dir = 'app/Events';
$count = 0;

foreach (glob("$dir/*.php") as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    if (strpos($content, 'LogManager') === false) {
        continue;
    }
    
    // Replace imports
    $content = str_replace(
        'use App\Services\LogManager;',
        'use Illuminate\Support\Facades\Log;',
        $content
    );
    
    // Replace full path app() calls
    $content = str_replace(
        'app(\App\Services\LogManager::class)->',
        'Log::',
        $content
    );
    
    // Replace short form app() calls
    $content = str_replace(
        'app(LogManager::class)->',
        'Log::',
        $content
    );
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        $count++;
        echo basename($file) . "\n";
    }
}

echo "\nTotal: $count files fixed\n";
