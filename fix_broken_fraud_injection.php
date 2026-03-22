<?php
/**
 * fix_broken_fraud_injection.php
 * Исправляет баги от fix_fraud_v2.ps1:
 *   1) $var = $this->fraudControlService->check(...); \nDB::transaction(  → правильный порядок
 *   2) return $this->fraudControlService->check(...); \n$x = DB::transaction( → убираем return
 *   3) Backtick `$var → $var внутри check() аргументов
 */
chdir(__DIR__);

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('app', FilesystemIterator::SKIP_DOTS)
);

$fixed = 0;
$errors = [];

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') continue;

    $path = $file->getPathname();
    $content = file_get_contents($path);
    $orig = $content;

    // ── Pattern 1: $var = (spaces) $this->fraudControlService->check(...);\nDB::transaction(
    // Fix: Remove $var= from check line, restore $var= before DB::transaction(
    $content = preg_replace_callback(
        '/(\s*)(\$\w+\s*=\s+)\s*(\$this->fraudControlService->check\((?:[^;]|\n)*?;\s*\n)(\s*)(DB::transaction\()/m',
        function ($m) {
            $indent   = $m[1];
            $varAssign = trim($m[2]); // e.g. "$booking ="
            $checkCall = $m[3];       // full check(...); line
            $indent2  = $m[4];
            $tx       = $m[5];
            // Remove leading spaces from checkCall line
            $checkCall = ltrim($checkCall);
            return $indent . $checkCall . $indent2 . $varAssign . ' ' . $tx;
        },
        $content
    );

    // ── Pattern 2: return $this->fraudControlService->check(...);\n(anything DB::transaction)
    // Fix: just remove the `return ` prefix — make it a standalone call
    $content = preg_replace_callback(
        '/(\s*)return\s+(\$this->fraudControlService->check\((?:[^;]|\n)*?;\s*\n)/m',
        function ($m) {
            $indent   = $m[1];
            $checkCall = $m[2];
            return $indent . $checkCall;
        },
        $content
    );

    // ── Pattern 3: backtick before $var in check() arguments: `$varName → $varName
    $content = preg_replace('/`(\$\w+)/', '$1', $content);

    // ── Pattern 4: standalone check() that has no semicolon gap before DB::transaction
    // Sometimes: $this->fraudControlService->check(...\n            );\nDB::transaction(
    // Already fine if Pattern 1 handled it — this is a safety pass

    // ── Pattern 5: broken indentation — DB::transaction( lost its $var = prefix entirely
    // Pattern: line starts with "DB::transaction(" without assignment on same line
    // AND previous function context had a variable — need to detect carefully
    // Only fix if there's no assignment on that line
    // This is handled by Pattern 1 above

    if ($content !== $orig) {
        file_put_contents($path, $content);
        $fixed++;
        echo "FIXED: " . str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $path) . "\n";
    }
}

echo "\n=== DONE: $fixed files fixed ===\n\n";

// Verification
echo "=== VERIFICATION ===\n";

$files2 = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('app', FilesystemIterator::SKIP_DOTS)
);

$brokenReturn = 0;
$brokenVar    = 0;
$backtick     = 0;

foreach ($files2 as $file) {
    if ($file->getExtension() !== 'php') continue;
    $c = file_get_contents($file->getPathname());
    $n = $file->getFilename();

    if (preg_match('/return\s+\$this->fraudControlService->check\(/', $c)) {
        echo "  STILL return+check: $n\n";
        $brokenReturn++;
    }
    if (preg_match('/\$\w+\s*=\s+\s*\$this->fraudControlService->check\(/', $c)) {
        echo "  STILL var=check: $n\n";
        $brokenVar++;
    }
    if (preg_match('/`\$\w+/', $c)) {
        $backtick++;
        echo "  STILL backtick: $n\n";
    }
}

echo "Broken return+check:  $brokenReturn\n";
echo "Broken var=check:     $brokenVar\n";
echo "Backtick issues:      $backtick\n";

if ($brokenReturn === 0 && $brokenVar === 0 && $backtick === 0) {
    echo "\nALL CLEAR ✅\n";
}
