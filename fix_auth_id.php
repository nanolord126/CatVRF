<?php

$dir = __DIR__ . '/app/Events';
$files = glob("$dir/*.php");
$fixed = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    // Check if file has auth()->id()
    if (strpos($content, 'auth()->id()') === false) {
        continue;
    }
    
    // Add Auth import if not present
    if (strpos($content, 'use Illuminate\Support\Facades\Auth;') === false) {
        $content = preg_replace(
            '/(namespace [^;]+;)/',
            "$1\n\nuse Illuminate\Support\Facades\Auth;",
            $content,
            1
        );
    }
    
    // Replace auth()->id() with Auth::id()
    $content = str_replace('auth()->id()', 'Auth::id()', $content);
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        $fixed[] = basename($file);
    }
}

if (!empty($fixed)) {
    echo "Fixed " . count($fixed) . " files:\n";
    foreach ($fixed as $file) {
        echo "  - $file\n";
    }
} else {
    echo "No files needed fixing\n";
}
