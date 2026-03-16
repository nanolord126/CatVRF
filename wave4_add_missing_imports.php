<?php
/**
 * WAVE 4: Add missing use Throwable statements
 */

$files = [
    'app/Console/Commands/Common/SendHealthReminders.php',
    'app/Filament/Admin/Pages/AIDashboard.php',
    'app/Filament/Tenant/Resources/Marketplace/ElectronicsResource/Pages/CreateElectronics.php',
    'app/Filament/Tenant/Resources/Marketplace/ElectronicsResource/Pages/EditElectronics.php',
];

$fixed = 0;

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    
    // Check if Throwable is used but not imported
    if (preg_match('/\bThrowable\b/', $content) && !preg_match('/use\s+Throwable;/m', $content)) {
        // Add after the last use statement
        $content = preg_replace(
            '/(use\s+[^;]+;)\n/',
            '$1' . "\nuse Throwable;",
            $content,
            1  // Only add once
        );
        
        // Make sure we don't add it twice
        $content = preg_replace('/use\s+Throwable;(\s*use\s+Throwable;)+/m', 'use Throwable;', $content);
        
        file_put_contents($file, $content);
        $fixed++;
        echo "✓ Added use Throwable; to " . str_replace('app/', '', $file) . "\n";
    }
}

echo "\n=== WAVE 4: Add Missing Imports ===\n";
echo "Files fixed: $fixed\n";
?>
