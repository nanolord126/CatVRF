<?php

$eventsDir = 'c:\\opt\\kotvrf\\CatVRF\\app\\Events';
$fixed = 0;

$files = scandir($eventsDir);
foreach ($files as $file) {
    if ($file === '.' || $file === '..' || !str_ends_with($file, '.php')) {
        continue;
    }
    
    $path = $eventsDir . '\\' . $file;
    $content = file_get_contents($path);
    
    if (strpos($content, 'LogManager') === false) {
        continue;
    }
    
    // 1. Replace LogManager imports
    $content = str_replace('use App\\Services\\LogManager;', 'use Illuminate\\Support\\Facades\\Log;', $content);
    
    // 2. Replace LogManager app() calls
    $content = str_replace('app(\\App\\Services\\LogManager::class)->', 'Log::', $content);
    $content = str_replace('app(LogManager::class)->', 'Log::', $content);
    
    // 3. Format PHP properly - add newlines after key points
    // After namespace
    $content = str_replace('namespace App\\Events;', "namespace App\\Events;\n", $content);
    
    // After closing tag and before namespace
    $content = str_replace('<?php', "<?php\n", $content);
    
    // Replace use; with use;\n
    $content = preg_replace('/(use [^;]+;)/', "$1\n", $content);
    
    // Add newlines before final class
    $content = str_replace('final class', "\nfinal class", $content);
    
    // Add newlines before methods
    $content = str_replace('public function', "\n\n    public function", $content);
    
    // Add newlines in try/catch
    $content = str_replace('try {', "try {\n            ", $content);
    $content = str_replace('} catch', "\n        } catch", $content);
    
    // Clean up excessive newlines
    $content = preg_replace('/\n\n\n+/', "\n\n", $content);
    
    // Save
    file_put_contents($path, $content);
    $fixed++;
    echo "$file\n";
}

echo "\nFixed: $fixed files\n";
