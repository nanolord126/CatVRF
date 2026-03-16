<?php

$dir = 'c:\\opt\\kotvrf\\CatVRF\\app\\Events';
$fixed = 0;

foreach (glob("$dir\\*.php") as $file) {
    $filename = basename($file);
    $content = file_get_contents($file);
    
    // Check if single line (very long)
    if (strlen($content) > 5000 && substr_count($content, "\n") < 5) {
        echo "Processing: $filename\n";
        
        // 1. Fix LogManager references
        $content = str_replace('use App\\Services\\LogManager;', 'use Illuminate\\Support\\Facades\\Log;', $content);
        $content = str_replace("app(\\App\\Services\\LogManager::class)->channel('audit')->info(", "Log::channel('audit')->info(", $content);
        $content = str_replace("app(\\App\\Services\\LogManager::class)->channel('errors')->error(", "Log::channel('errors')->error(", $content);
        $content = str_replace("app(LogManager::class)->channel('audit')->info(", "Log::channel('audit')->info(", $content);
        $content = str_replace("app(LogManager::class)->channel('errors')->error(", "Log::channel('errors')->error(", $content);
        
        // 2. Format PHP properly - add newlines
        // After namespace
        $content = str_replace('namespace App\\Events;', "namespace App\\Events;\n", $content);
        
        // After use statements
        $content = str_replace('; use ', ";\nuse ", $content);
        
        // After class definition opening brace
        $content = str_replace('{use ', "{\n\n    use ", $content);
        
        // After properties
        $content = str_replace('final class ', "final class ", $content);
        $content = str_replace('public readonly ', "\n    public readonly ", $content);
        
        // After constructor
        $content = str_replace('public function __construct', "\n    public function __construct", $content);
        
        // After methods
        $content = str_replace('public function broadcastOn', "\n\n    public function broadcastOn", $content);
        $content = str_replace('public function broadcastAs', "\n\n    public function broadcastAs", $content);
        $content = str_replace('public function broadcastWith', "\n\n    public function broadcastWith", $content);
        
        // Format arrays and try/catch
        $content = str_replace('[ ', "[\n        ", $content);
        $content = str_replace(' ]', "\n    ]", $content);
        $content = str_replace('try {', "try {\n            ", $content);
        $content = str_replace('} catch', "\n        } catch", $content);
        
        // Clean up multiple newlines
        $content = preg_replace("/\n\s*\n/", "\n", $content);
        
        file_put_contents($file, $content);
        $fixed++;
    }
}

echo "\n✓ Processed $fixed files\n";
