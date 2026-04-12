<?php declare(strict_types=1);

/**
 * Scan for wrong AuditService::log() calls
 * 
 * Correct signature: log(string $operation, array $data, string $correlationId, array $metadata = [])
 * Wrong patterns:
 *   audit->log('action', ModelClass::class, $id, $old, $new, $correlationId) — 6 args
 *   audit->log('action', 'subject_type', $id) — class string as 2nd arg
 */

$baseDir = __DIR__ . '/app';
$issues = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    if ($file->getBasename() === 'AuditService.php') continue;
    
    $path = $file->getPathname();
    $content = file_get_contents($path);
    
    if (strpos($content, 'audit') === false && strpos($content, 'Audit') === false) continue;
    
    $lines = explode("\n", $content);
    
    foreach ($lines as $num => $line) {
        // Look for audit->log( with more than 4 args
        // Pattern: audit->log('operation', Something::class, $id,
        if (preg_match('/audit\s*->\s*log\s*\(\s*[\'"](\w+)[\'"]/', $line, $m)) {
            // Count args - look for class::class as 2nd arg
            if (preg_match('/audit\s*->\s*log\s*\([^,]+,\s*\w+::class/', $line)) {
                $lineNum = $num + 1;
                $short = str_replace(str_replace('/', '\\', __DIR__) . '\\', '', $path);
                $issues[] = "CLASS_ARG L{$lineNum}: {$short} => " . trim($line);
            }
            // Count commas (rough count)
            $fromLog = substr($line, strpos($line, 'audit'));
            $commaCount = substr_count($fromLog, ',');
            if ($commaCount >= 4) {
                $lineNum = $num + 1;
                $short = str_replace(str_replace('/', '\\', __DIR__) . '\\', '', $path);
                $issues[] = "MANY_ARGS({$commaCount}) L{$lineNum}: {$short} => " . trim($line);
            }
        }
    }
}

echo "AuditService misuse found: " . count($issues) . "\n\n";
foreach ($issues as $i) echo $i . "\n";
