<?php
declare(strict_types=1);

/**
 * scan_and_fix_all.php
 * 
 * Массовое исправление всех оставшихся проблем:
 * 1. Class$this->prop -> ClassSession:: (или правильный класс)
 * 2. \App\Domains\Core\Models\BusinessGroup -> \App\Models\BusinessGroup
 * 3. Undefined ClassReminderNotification -> создать заглушку
 */

$baseDir = __DIR__;
$dirs = ['app', 'modules', 'routes'];

$stats = [
    'scanned' => 0,
    'fixed' => 0,
    'patterns' => [],
];

// Паттерны замены: [pattern, replacement, description]
$replacements = [
    // Class$this->session -> ClassSession
    [
        'pattern' => '/\bClass\$this->session\b/',
        'replacement' => 'ClassSession',
        'desc' => 'Class$this->session -> ClassSession',
    ],
    // Class$this->anyProp:: -> ClassName::  (generic, логирует для ручного просмотра)
    // \App\Domains\Core\Models\BusinessGroup -> \App\Models\BusinessGroup
    [
        'pattern' => '/\\\\App\\\\Domains\\\\Core\\\\Models\\\\BusinessGroup/',
        'replacement' => '\\App\\Models\\BusinessGroup',
        'desc' => '\App\Domains\Core\Models\BusinessGroup -> \App\Models\BusinessGroup',
    ],
    // App\Domains\Core\Models\BusinessGroup (без ведущего слэша)
    [
        'pattern' => '/(?<!\\\\)App\\\\Domains\\\\Core\\\\Models\\\\BusinessGroup/',
        'replacement' => 'App\\Models\\BusinessGroup',
        'desc' => 'App\Domains\Core\Models\BusinessGroup -> App\Models\BusinessGroup',
    ],
];

function catvrf_fixFile(string $path, array $replacements, array &$stats): void
{
    $original = file_get_contents($path);
    if ($original === false) return;

    $content = $original;
    $changed = false;

    foreach ($replacements as $r) {
        $new = preg_replace($r['pattern'], $r['replacement'], $content);
        if ($new !== $content) {
            $content = $new;
            $changed = true;
            $stats['patterns'][$r['desc']] = ($stats['patterns'][$r['desc']] ?? 0) + 1;
        }
    }

    if ($changed) {
        file_put_contents($path, $content);
        $stats['fixed']++;
        echo "FIXED: $path\n";
    }
}

function catvrf_scanDir(string $dir, array $replacements, array &$stats): void
{
    if (!is_dir($dir)) return;

    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));

    foreach ($iter as $file) {
        if ($file->getExtension() !== 'php') continue;
        $stats['scanned']++;
        catvrf_fixFile($file->getPathname(), $replacements, $stats);
    }
}

// Сначала найти все оставшиеся Class$this-> (кроме session)
echo "=== Phase 1: Scanning for Class\$this-> patterns ===\n";
$remaining = [];
foreach ($dirs as $dir) {
    $fullDir = $baseDir . DIRECTORY_SEPARATOR . $dir;
    if (!is_dir($fullDir)) continue;
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullDir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        if ($file->getExtension() !== 'php') continue;
        $content = file_get_contents($file->getPathname());
        if (preg_match_all('/[A-Z][a-zA-Z0-9_]+\$this->[a-z][a-zA-Z0-9_]*/', $content, $matches)) {
            $remaining[$file->getPathname()] = $matches[0];
        }
    }
}

if (count($remaining) === 0) {
    echo "No Class\$this-> patterns found!\n";
} else {
    echo count($remaining) . " files with remaining Class\$this-> patterns:\n";
    foreach ($remaining as $file => $matches) {
        echo "  FILE: $file\n";
        foreach (array_unique($matches) as $m) {
            echo "    MATCH: $m\n";
        }
    }
}

// Phase 2: Apply known safe replacements
echo "\n=== Phase 2: Applying safe replacements ===\n";
foreach ($dirs as $dir) {
    catvrf_scanDir($baseDir . DIRECTORY_SEPARATOR . $dir, $replacements, $stats);
}

// Phase 3: Find \App\Domains\Core references
echo "\n=== Phase 3: Scanning for \App\Domains\Core\Models\ references ===\n";
$coreRefs = [];
foreach ($dirs as $dir) {
    $fullDir = $baseDir . DIRECTORY_SEPARATOR . $dir;
    if (!is_dir($fullDir)) continue;
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullDir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        if ($file->getExtension() !== 'php') continue;
        $content = file_get_contents($file->getPathname());
        if (strpos($content, 'App\\Domains\\Core\\') !== false || strpos($content, 'App\Domains\Core\\') !== false) {
            $coreRefs[] = $file->getPathname();
        }
    }
}

if (count($coreRefs) > 0) {
    echo count($coreRefs) . " files with \\App\\Domains\\Core\\ references:\n";
    foreach ($coreRefs as $f) echo "  $f\n";
} else {
    echo "None found.\n";
}

echo "\n=== Summary ===\n";
echo "Scanned: {$stats['scanned']} files\n";
echo "Fixed: {$stats['fixed']} files\n";
foreach ($stats['patterns'] as $desc => $count) {
    echo "  $desc: $count\n";
}
