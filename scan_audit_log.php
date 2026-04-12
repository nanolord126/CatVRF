<?php
declare(strict_types=1);
/**
 * scan_audit_log.php
 * Сканирует ->audit->log( вызовы (AuditService instance method).
 * Классифицирует:
 *   Pattern A: log(action:...) — broken, named args не совпадают с сигнатурой log()
 *   Pattern B: log('op', $data, $cid) — positional, valid deprecated
 */

$dir = __DIR__ . '/app';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$results    = [];
$totalCalls = 0;
$patternA   = 0;
$patternB   = 0;

foreach ($files as $f) {
    if ($f->getExtension() !== 'php') continue;
    $path    = $f->getPathname();
    $content = file_get_contents($path);

    if (strpos($content, '->audit->log(') === false) continue;

    $lines     = explode("\n", $content);
    $n         = count($lines);
    $fileCalls = [];

    $i = 0;
    while ($i < $n) {
        $line = $lines[$i];
        if (!preg_match('/->audit->log\s*\(/', $line)) { $i++; continue; }

        // Buffer full call (balance parens)
        $block = [$line];
        $logPos = strpos($line, '->audit->log');
        $depth  = substr_count(substr($line, $logPos), '(')
                - substr_count(substr($line, $logPos), ')');
        $j = $i + 1;
        while ($depth > 0 && $j < $n) {
            $block[] = $lines[$j];
            $depth  += substr_count($lines[$j], '(') - substr_count($lines[$j], ')');
            $j++;
        }

        $callStr = implode("\n", $block);
        $isA     = (bool) preg_match('/\baction\s*:/', $callStr);
        $preview = trim(preg_replace('/\s+/', ' ', $callStr));
        if (strlen($preview) > 160) $preview = substr($preview, 0, 160) . '...';

        $fileCalls[] = ['line' => $i + 1, 'patternA' => $isA, 'preview' => $preview];
        $isA ? $patternA++ : $patternB++;
        $i = $j;
    }

    if ($fileCalls) {
        $rel            = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $path);
        $results[$rel]  = $fileCalls;
        $totalCalls    += count($fileCalls);
    }
}

// ── Output ──────────────────────────────────────────────────────
echo "=== scan_audit_log: ->audit->log() calls ===\n";
echo "Total files : " . count($results) . "\n";
echo "Total calls : $totalCalls\n";
echo "Pattern A   : $patternA  (action: named args → BROKEN, needs log→record fix)\n";
echo "Pattern B   : $patternB  (positional args   → valid deprecated)\n";
echo "\n";

foreach ($results as $rel => $calls) {
    $cntA = count(array_filter($calls, fn($c) => $c['patternA']));
    $cntB = count($calls) - $cntA;
    echo "[$rel]  A=$cntA B=$cntB\n";
    foreach ($calls as $c) {
        $tag = $c['patternA'] ? '[A-BROKEN]' : '[B-valid ]';
        echo "  line {$c['line']} $tag  {$c['preview']}\n";
    }
    echo "\n";
}
