<?php

// Array of files and their corresponding replacements
$fixes = [
    'AttendanceCheckedOut.php' => [
        'import' => 'use Illuminate\Support\Facades\Auth;',
        'search' => '$this->userId = auth()->id() ?? 0;',
        'replace' => '$this->userId = Auth::id() ?? 0;',
    ],
    'ProductUpdated.php' => [
        'import' => 'use Illuminate\Support\Facades\Auth;',
        'search' => '$this->userId = auth()->id() ?? 0;',
        'replace' => '$this->userId = Auth::id() ?? 0;',
    ],
    'ProductCreated.php' => [
        'import' => 'use Illuminate\Support\Facades\Auth;',
        'search' => '$this->userId = auth()->id() ?? 0;',
        'replace' => '$this->userId = Auth::id() ?? 0;',
    ],
    'PayrollRunProcessed.php' => [
        'import' => 'use Illuminate\Support\Facades\Auth;',
        'search' => '$this->userId = auth()->id() ?? 0;',
        'replace' => '$this->userId = Auth::id() ?? 0;',
    ],
    'PayrollRunCreated.php' => [
        'import' => 'use Illuminate\Support\Facades\Auth;',
        'search' => '$this->userId = auth()->id() ?? 0;',
        'replace' => '$this->userId = Auth::id() ?? 0;',
    ],
    'EmployeeUpdated.php' => [
        'import' => 'use Illuminate\Support\Facades\Auth;',
        'search' => '$this->userId = auth()->id() ?? 0;',
        'replace' => '$this->userId = Auth::id() ?? 0;',
    ],
    'EmployeeRestored.php' => [
        'import' => 'use Illuminate\Support\Facades\Auth;',
        'search' => '$this->userId = auth()->id() ?? 0;',
        'replace' => '$this->userId = Auth::id() ?? 0;',
    ],
    'EmployeeDeleted.php' => [
        'import' => 'use Illuminate\Support\Facades\Auth;',
        'search' => '$this->userId = auth()->id() ?? 0;',
        'replace' => '$this->userId = Auth::id() ?? 0;',
    ],
    'EmployeeCreated.php' => [
        'import' => 'use Illuminate\Support\Facades\Auth;',
        'search' => '$this->userId = auth()->id() ?? 0;',
        'replace' => '$this->userId = Auth::id() ?? 0;',
    ],
];

$dir = __DIR__ . '/app/Events';
$fixed = [];

foreach ($fixes as $file => $config) {
    $path = "$dir/$file";
    if (!file_exists($path)) continue;
    
    $content = file_get_contents($path);
    $original = $content;
    
    // Add import if missing
    if (strpos($content, $config['import']) === false) {
        $content = preg_replace(
            '/(use Illuminate\\\\Support\\\\Str;)/',
            "$config[import]\n$1",
            $content
        );
    }
    
    // Replace auth()->id() with Auth::id()
    $content = str_replace($config['search'], $config['replace'], $content);
    
    if ($content !== $original) {
        file_put_contents($path, $content);
        $fixed[] = $file;
    }
}

if (!empty($fixed)) {
    echo "Fixed " . count($fixed) . " files:\n";
    foreach ($fixed as $f) {
        echo "  ✓ $f\n";
    }
} else {
    echo "No changes needed\n";
}
