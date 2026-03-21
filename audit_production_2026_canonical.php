<?php declare(strict_types=1);

$appPath = __DIR__ . '/app';
$databasePath = __DIR__ . '/database/migrations';
$configPath = __DIR__ . '/config';

$issues = [
    'no_declare' => [],
    'no_crlf' => [],
    'no_utf8' => [],
    'no_final' => [],
];

$needsDeclare = [
    'app/Http/Controllers',
    'app/Models',
    'app/Services',
    'app/Jobs',
    'app/Events',
    'app/Listeners',
    'app/Providers',
    'app/Policies',
    'app/Http/Requests',
    'app/DTOs',
    'app/Enums',
];

function checkFile(string $file, array &$issues, array $needsDeclare): void {
    if (!is_file($file) || !str_ends_with($file, '.php')) {
        return;
    }

    $content = file_get_contents($file);
    $relativePath = str_replace(__DIR__ . '/', '', $file);

    // Check UTF-8 BOM
    if (str_starts_with($content, "\xEF\xBB\xBF")) {
        $issues['no_utf8'][] = $relativePath;
    }

    // Check CRLF
    if (!str_contains($content, "\r\n")) {
        $issues['no_crlf'][] = $relativePath;
    }

    // Check declare for specific folders
    $needsDeclareFoFile = false;
    foreach ($needsDeclare as $folder) {
        if (str_contains($relativePath, $folder)) {
            $needsDeclareFoFile = true;
            break;
        }
    }

    if ($needsDeclareFoFile && !str_contains($content, 'declare(strict_types=1);')) {
        $issues['no_declare'][] = $relativePath;
    }

    // Check final class (for models and DTOs)
    if ((str_contains($relativePath, 'Models') || str_contains($relativePath, 'DTOs')) 
        && str_contains($content, 'class ') && !str_contains($content, 'final class')) {
        $issues['no_final'][] = $relativePath;
    }
}

// Scan app directory
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($appPath),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($files as $file) {
    checkFile($file->getPathname(), $issues, $needsDeclare);
}

// Output report
echo "=== PRODUCTION 2026 CANONICAL AUDIT ===\n\n";

if (count($issues['no_declare']) > 0) {
    echo "❌ Missing declare(strict_types=1) (" . count($issues['no_declare']) . "):\n";
    foreach (array_slice($issues['no_declare'], 0, 10) as $file) {
        echo "   - $file\n";
    }
    if (count($issues['no_declare']) > 10) {
        echo "   ... and " . (count($issues['no_declare']) - 10) . " more\n";
    }
    echo "\n";
}

if (count($issues['no_crlf']) > 0) {
    echo "❌ Missing CRLF line endings (" . count($issues['no_crlf']) . "):\n";
    foreach (array_slice($issues['no_crlf'], 0, 10) as $file) {
        echo "   - $file\n";
    }
    if (count($issues['no_crlf']) > 10) {
        echo "   ... and " . (count($issues['no_crlf']) - 10) . " more\n";
    }
    echo "\n";
}

if (count($issues['no_utf8']) > 0) {
    echo "❌ Has UTF-8 BOM (" . count($issues['no_utf8']) . "):\n";
    foreach (array_slice($issues['no_utf8'], 0, 10) as $file) {
        echo "   - $file\n";
    }
    if (count($issues['no_utf8']) > 10) {
        echo "   ... and " . (count($issues['no_utf8']) - 10) . " more\n";
    }
    echo "\n";
}

if (count($issues['no_final']) > 0) {
    echo "⚠️  Missing final keyword (" . count($issues['no_final']) . "):\n";
    foreach (array_slice($issues['no_final'], 0, 10) as $file) {
        echo "   - $file\n";
    }
    if (count($issues['no_final']) > 10) {
        echo "   ... and " . (count($issues['no_final']) - 10) . " more\n";
    }
    echo "\n";
}

$totalIssues = array_sum(array_map('count', $issues));
if ($totalIssues === 0) {
    echo "✅ ALL FILES COMPLIANT WITH PRODUCTION 2026 CANONICAL!\n";
} else {
    echo "📊 Total issues: $totalIssues\n";
}
