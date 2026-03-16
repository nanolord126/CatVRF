<?php

$dir = __DIR__ . '/app/Events';
$fixed = 0;

foreach (glob("$dir/*.php") as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    if (strpos($content, 'LogManager') === false) {
        continue;
    }
    
    // Replace: use App\Services\LogManager; with use Illuminate\Support\Facades\Log;
    $content = str_replace(
        'use App\Services\LogManager;',
        'use Illuminate\Support\Facades\Log;',
        $content
    );
    
    // Replace: app(\App\Services\LogManager::class)->channel('audit')->info(
    $content = str_replace(
        "app(\App\Services\LogManager::class)->channel('audit')->info(",
        "Log::channel('audit')->info(",
        $content
    );
    
    // Replace: app(\App\Services\LogManager::class)->channel('errors')->error(
    $content = str_replace(
        "app(\App\Services\LogManager::class)->channel('errors')->error(",
        "Log::channel('errors')->error(",
        $content
    );
    
    // Replace shorter form: app(LogManager::class)->channel('audit')->info(
    $content = str_replace(
        "app(LogManager::class)->channel('audit')->info(",
        "Log::channel('audit')->info(",
        $content
    );
    
    // Replace shorter form: app(LogManager::class)->channel('errors')->error(
    $content = str_replace(
        "app(LogManager::class)->channel('errors')->error(",
        "Log::channel('errors')->error(",
        $content
    );
    
    // Add Log import if needed (check if we have Log facade already)
    if (strpos($content, 'use Illuminate\Support\Facades\Log;') === false && 
        strpos($content, "Log::channel") !== false) {
        
        // Add Log import after Auth if exists
        if (strpos($content, 'use Illuminate\Support\Facades\Auth;') !== false) {
            $content = str_replace(
                'use Illuminate\Support\Facades\Auth;',
                "use Illuminate\Support\Facades\Auth;\nuse Illuminate\Support\Facades\Log;",
                $content
            );
        }
        // Otherwise add after other Illuminate imports
        elseif (preg_match('/use Illuminate[^;]+;/', $content)) {
            $content = preg_replace(
                '/(use Illuminate[^;]+;)/',
                "$1\nuse Illuminate\Support\Facades\Log;",
                $content,
                1
            );
        }
        // Otherwise add after namespace
        else {
            $content = str_replace(
                'namespace App\Events;',
                "namespace App\Events;\n\nuse Illuminate\Support\Facades\Log;",
                $content
            );
        }
    }
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "✓ Fixed: " . basename($file) . "\n";
        $fixed++;
    }
}

echo "\nTotal fixed: $fixed files\n";
