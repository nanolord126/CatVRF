<?php
chdir('c:/opt/kotvrf/CatVRF');

$returnFCS = $varFCS = $noCheck = $broken = $dupUse = $good = [];

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('app/Domains', FilesystemIterator::SKIP_DOTS)
);
foreach ($it as $f) {
    if ($f->getExtension() !== 'php') continue;
    if (!str_contains($f->getPathname(), DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR)) continue;
    $c = file_get_contents($f->getPathname());
    if (!str_contains($c, 'fraudControlService')) continue;

    if (preg_match('/^\s*return \$this->fraudControlService->check\(/m', $c))
        $returnFCS[] = $f->getPathname();
    elseif (preg_match('/^\s*\$\w+\s*=\s*\$this->fraudControlService->check\(/m', $c))
        $varFCS[] = $f->getPathname();
    elseif (!str_contains($c, '->check('))
        $noCheck[] = $f->getPathname();
    else
        $good[] = $f->getFilename();

    if (str_contains($c, 'FraudControlService \\,')) $broken[] = $f->getFilename();
    if (substr_count($c, 'use App\\Services\\FraudControlService;') > 1) $dupUse[] = $f->getFilename();
}

echo "=== AUDIT ===\n";
echo "return FCS (CRITICAL): " . count($returnFCS) . "\n";
echo "var=FCS (CRITICAL):    " . count($varFCS) . "\n";
echo "no ->check():          " . count($noCheck) . "\n";
echo "broken injection:      " . count($broken) . "\n";
echo "dup use:               " . count($dupUse) . "\n";
echo "good (correct):        " . count($good) . "\n";
foreach (array_slice($returnFCS, 0, 8) as $n) echo "  retFCS: $n\n";
foreach (array_slice($varFCS, 0, 5) as $n)    echo "  varFCS: $n\n";
