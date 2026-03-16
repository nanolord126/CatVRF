<?php

$files = [
    'EmployeeDeleted',
    'EmployeeUpdated',
    'EmployeeRestored',
    'EmployeeCreated',
    'PayrollRunCreated',
    'ProductUpdated',
    'PayrollRunProcessed',
    'ProductCreated',
];

$dir = __DIR__ . '/app/Events';
$fixed = [];

foreach ($files as $file) {
    $path = "$dir/{$file}.php";
    if (!file_exists($path)) continue;
    
    $content = file_get_contents($path);
    $original = $content;
    
    // Add Auth import if not present
    if (strpos($content, 'use Illuminate\Support\Facades\Auth;') === false) {
        $content = preg_replace(
            '/(use Illuminate\\\\Support\\\\Str;)/',
            'use Illuminate\\Support\\Facades\\Auth;' . "\n" . '$1',
            $content
        );
    }
    
    // Replace auth()->id() with Auth::id()
    $content = str_replace('auth()->id()', 'Auth::id()', $content);
    
    if ($content !== $original) {
        file_put_contents($path, $content);
        $fixed[] = $file;
    }
}

if (!empty($fixed)) {
    echo "Fixed " . count($fixed) . " files\n";
}
