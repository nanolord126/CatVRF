<?php

// Files with duplicate property declarations
$filesToFix = [
    'StockMovement' => ['direction', 'reason', 'newBalance'],
    'LeaveRequestApproved' => ['approvedByName'],
    'OrderCreated' => ['totalAmount', 'itemCount'],
    'OrderStatusChanged' => ['previousStatus', 'newStatus'],
];

$baseDir = __DIR__ . '/app/Events';

foreach ($filesToFix as $fileName => $propsToRemove) {
    $filePath = "$baseDir/{$fileName}.php";
    
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    foreach ($propsToRemove as $prop) {
        // Match: public readonly TYPE $propName; (including comments)
        $pattern = '/^\s*public\s+readonly\s+\w+\s+\$' . preg_quote($prop) . '\s*;.*$/m';
        $content = preg_replace($pattern, '', $content);
    }
    
    // Clean up multiple consecutive newlines
    $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
    
    file_put_contents($filePath, $content);
    echo "Fixed: {$fileName}.php\n";
}

echo "\nDone fixing duplicate properties\n";
