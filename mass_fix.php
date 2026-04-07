<?php
/**
 * Mass fixer for CANON 2026 violations across ALL domains.
 * Categories:
 * 1. BadgeColumn → TextColumn::badge()
 * 2. BooleanColumn → IconColumn::boolean()
 * 3. tenant('id') in Filament Resources → filament()->getTenant()?->id
 * 4. Facades\DB, Facades\Log in GroceryAndDelivery → injected
 */

$basePath = __DIR__;
$fixed = 0;
$errors = [];

// ============================================================
// 1. Fix BadgeColumn → TextColumn::badge() in all Filament Resources
// ============================================================
$badgeColumnFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($basePath . '/app/Domains', RecursiveDirectoryIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $content = file_get_contents($file->getPathname());
    if (str_contains($content, 'BadgeColumn')) {
        $badgeColumnFiles[] = $file->getPathname();
    }
}

foreach ($badgeColumnFiles as $filePath) {
    $content = file_get_contents($filePath);
    $original = $content;

    // Remove BadgeColumn imports
    $content = preg_replace('/use\s+Filament\\\\Tables\\\\Columns\\\\BadgeColumn;\s*\n/', '', $content);
    $content = preg_replace('/use\s+Tables\\\\Columns\\\\BadgeColumn;\s*\n/', '', $content);

    // Pattern 1: BadgeColumn::make('X')->colors([...])  → TextColumn::make('X')->badge()
    // Simple colors array with arrow notation
    $content = preg_replace(
        '/BadgeColumn::make\(([\'"][^\'"]+[\'"])\)->colors\(\[[\s\S]*?\]\)/',
        'TextColumn::make($1)->badge()',
        $content
    );

    // Pattern 2: Tables\Columns\BadgeColumn::make('X') with colors on next lines
    $content = preg_replace(
        '/Tables\\\\Columns\\\\BadgeColumn::make\(([\'"][^\'"]+[\'"])\)\s*\n\s*->colors\(\[[\s\S]*?\]\)/',
        'Tables\\Columns\\TextColumn::make($1)->badge()',
        $content
    );

    // Pattern 3: Simple BadgeColumn::make('X'), or BadgeColumn::make('X')
    $content = str_replace('BadgeColumn::make(', 'TextColumn::make(', $content);

    // Ensure ->badge() is present after the replacement (if not already there)
    // Find TextColumn::make('status') that was just replaced and doesn't have ->badge()
    $content = preg_replace(
        '/(TextColumn::make\([\'"](?:status|type|severity|event_type|role|vertical|current_stock)[\'"])\)(?!\s*->badge)/',
        '$1)->badge()',
        $content
    );

    // Pattern 4: BadgeColumn in form (wrong usage) → Toggle
    // BadgeColumn::make('is_verified')->label('Verified') in form schema
    $content = preg_replace(
        '/BadgeColumn::make\(([\'"]is_\w+[\'"])\)->label\(([\'"][^\'"]+[\'"])\)/',
        'Toggle::make($1)->label($2)',
        $content
    );

    if ($content !== $original) {
        file_put_contents($filePath, $content);
        $rel = str_replace($basePath . '\\', '', $filePath);
        echo "  FIXED BadgeColumn: $rel\n";
        $fixed++;
    }
}

// ============================================================
// 2. Fix BooleanColumn → IconColumn::boolean()
// ============================================================
$boolFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($basePath . '/app/Domains', RecursiveDirectoryIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $content = file_get_contents($file->getPathname());
    if (str_contains($content, 'BooleanColumn')) {
        $boolFiles[] = $file->getPathname();
    }
}

foreach ($boolFiles as $filePath) {
    $content = file_get_contents($filePath);
    $original = $content;

    $content = preg_replace('/use\s+Filament\\\\Tables\\\\Columns\\\\BooleanColumn;\s*\n/', '', $content);
    $content = str_replace('BooleanColumn::make(', 'IconColumn::make(', $content);

    // Ensure ->boolean() after IconColumn::make('is_xxx')
    $content = preg_replace(
        '/(IconColumn::make\([\'"]is_\w+[\'"])\)(?!\s*->boolean)/',
        '$1)->boolean()',
        $content
    );

    if ($content !== $original) {
        file_put_contents($filePath, $content);
        $rel = str_replace($basePath . '\\', '', $filePath);
        echo "  FIXED BooleanColumn: $rel\n";
        $fixed++;
    }
}

// ============================================================
// 3. Fix tenant('id') in Filament Resources → filament()->getTenant()?->id
// ============================================================
$filamentDirs = [
    'app/Domains/Sports/Filament',
    'app/Domains/Sports/Fitness/Filament',
    'app/Domains/Tickets/Filament',
    'app/Domains/Logistics/Filament',
];

foreach ($filamentDirs as $dir) {
    $fullDir = $basePath . '/' . $dir;
    if (!is_dir($fullDir)) continue;

    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iter as $file) {
        if ($file->getExtension() !== 'php') continue;
        $content = file_get_contents($file->getPathname());
        $original = $content;

        $content = str_replace("tenant('id')", "filament()->getTenant()?->id", $content);

        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            $rel = str_replace($basePath . '\\', '', $file->getPathname());
            echo "  FIXED tenant('id') in Filament: $rel\n";
            $fixed++;
        }
    }
}

// ============================================================
// 4. Fix Facades\DB + Facades\Log in GroceryAndDelivery
// ============================================================
$groceryDir = $basePath . '/app/Domains/GroceryAndDelivery';
if (is_dir($groceryDir)) {
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($groceryDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iter as $file) {
        if ($file->getExtension() !== 'php') continue;
        $content = file_get_contents($file->getPathname());
        $original = $content;

        $hasDbFacade = str_contains($content, 'use Illuminate\\Support\\Facades\\DB;');
        $hasLogFacade = str_contains($content, 'use Illuminate\\Support\\Facades\\Log;');

        if (!$hasDbFacade && !$hasLogFacade) continue;

        // For Services: replace facade import + usage with injected
        if (str_contains($file->getPathname(), 'Services')) {
            if ($hasDbFacade) {
                $content = str_replace(
                    'use Illuminate\\Support\\Facades\\DB;',
                    'use Illuminate\\Database\\DatabaseManager;',
                    $content
                );
                // Add to constructor if not already there
                if (str_contains($content, 'private FraudControlService') || str_contains($content, 'public function __construct')) {
                    // Add DatabaseManager to constructor
                    $content = preg_replace(
                        '/(public function __construct\(\s*\n)([\s\S]*?)(\s*\) \{\})/',
                        "$1$2        private DatabaseManager \$db,\n$3",
                        $content
                    );
                }
                $content = str_replace('DB::', '$this->db->', $content);
            }
            if ($hasLogFacade) {
                $content = str_replace(
                    'use Illuminate\\Support\\Facades\\Log;',
                    'use Psr\\Log\\LoggerInterface;',
                    $content
                );
                if (str_contains($content, 'public function __construct')) {
                    $content = preg_replace(
                        '/(public function __construct\(\s*\n)([\s\S]*?)(\s*\) \{\})/',
                        "$1$2        private LoggerInterface \$logger,\n$3",
                        $content
                    );
                }
                $content = str_replace('Log::', '$this->logger->', $content);
            }
        }

        // For Jobs: replace Facades with app() calls
        if (str_contains($file->getPathname(), 'Jobs')) {
            if ($hasDbFacade) {
                $content = str_replace('use Illuminate\\Support\\Facades\\DB;', '', $content);
                $content = str_replace('DB::', 'app(\\Illuminate\\Database\\DatabaseManager::class)->', $content);
            }
            if ($hasLogFacade) {
                $content = str_replace('use Illuminate\\Support\\Facades\\Log;', '', $content);
                $content = str_replace('Log::', 'app(\\Psr\\Log\\LoggerInterface::class)->', $content);
            }
        }

        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            $rel = str_replace($basePath . '\\', '', $file->getPathname());
            echo "  FIXED Facades: $rel\n";
            $fixed++;
        }
    }
}

echo "\n=== Total files fixed: $fixed ===\n";

// ============================================================
// Syntax check all fixed files
// ============================================================
echo "\nRunning syntax check on all Domains...\n";
$syntaxErrors = 0;
$checked = 0;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($basePath . '/app/Domains', RecursiveDirectoryIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $checked++;
    $output = [];
    exec("php -l \"{$file->getPathname()}\" 2>&1", $output, $code);
    if ($code !== 0) {
        $syntaxErrors++;
        $rel = str_replace($basePath . '\\', '', $file->getPathname());
        echo "  SYNTAX ERROR: $rel\n";
        echo "    " . implode("\n    ", $output) . "\n";
    }
}
echo "\nSyntax check: $checked files, $syntaxErrors errors\n";
