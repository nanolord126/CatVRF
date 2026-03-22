<?php
// mock_fcs_in_tests.php — добавляет mock FraudControlService во все тесты без него
chdir('c:\\opt\\kotvrf\\CatVRF');

$testFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('tests', FilesystemIterator::SKIP_DOTS)
);

$fixed = 0;
foreach ($testFiles as $file) {
    if ($file->getExtension() !== 'php') continue;
    $name = $file->getFilename();
    if (!preg_match('/(Service|Controller)Test\.php$/', $name)) continue;

    $content = file_get_contents($file->getPathname());
    if (str_contains($content, 'FraudControlService')) continue;

    $orig = $content;

    // 1. Add use import after first use statement
    $content = preg_replace(
        '/^(use [^\r\n]+;)/m',
        "$1\nuse App\\Services\\FraudControlService;",
        $content,
        1
    );

    // 2. Add mock line at end of setUp() — find closing brace of setUp
    if (preg_match('/protected function setUp\(\): void\s*\{([^}]*)\}/s', $content, $m, PREG_OFFSET_CAPTURE)) {
        $blockStart = $m[0][1];
        $blockLen   = strlen($m[0][0]);
        $insertPos  = $blockStart + $blockLen - 1; // before closing }
        $mockLine   = "\n        \$this->mock(FraudControlService::class)->shouldReceive('check')->andReturn(true);";
        $content    = substr($content, 0, $insertPos) . $mockLine . "\n    " . substr($content, $insertPos);
    }

    if ($content !== $orig) {
        file_put_contents($file->getPathname(), $content);
        echo "  MOCKED: $name\n";
        $fixed++;
    }
}

echo "\nDone: $fixed test files updated\n";

// FINAL STATS
$allTests = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('tests', FilesystemIterator::SKIP_DOTS)
);
$stillMissing = 0;
foreach ($allTests as $f) {
    if ($f->getExtension() !== 'php') continue;
    if (!preg_match('/(Service|Controller)Test\.php$/', $f->getFilename())) continue;
    if (!str_contains(file_get_contents($f->getPathname()), 'FraudControlService')) {
        echo "  STILL MISSING: {$f->getFilename()}\n";
        $stillMissing++;
    }
}
echo "Still missing: $stillMissing\n";
