<?php
/**
 * Fix bavix/laravel-wallet typed constants for PHP 8.2 compatibility
 */

$files = [
    __DIR__ . '/vendor/bavix/laravel-wallet/src/Models/Transfer.php',
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "❌ File not found: $file\n";
        continue;
    }

    $content = file_get_contents($file);
    $original = $content;
    
    // Remove string type from constants
    $content = preg_replace('/\bfinal public const string /', 'final public const ', $content);
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "✅ Fixed: $file\n";
    } else {
        echo "⏭️  No changes needed: $file\n";
    }
}

echo "\n✅ Done!\n";
