<?php
// Fast line ending converter: LF -> CRLF
$start = microtime(true);
$count = 0;
$errors = 0;

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    
    // Skip vendor
    if (strpos($path, '\vendor\\') !== false || strpos($path, '/vendor/') !== false) {
        continue;
    }

    try {
        $content = file_get_contents($path);
        if ($content === false) {
            $errors++;
            continue;
        }

        // Convert LF to CRLF
        $content = str_replace("\r\n", "\n", $content); // normalize first
        $content = str_replace("\n", "\r\n", $content); // then convert

        if (file_put_contents($path, $content, LOCK_EX) === false) {
            $errors++;
            continue;
        }

        $count++;
        if ($count % 500 === 0) {
            echo "[✓] Converted $count files...\n";
        }
    } catch (Throwable $e) {
        $errors++;
    }
}

$time = microtime(true) - $start;
echo "\n[DONE] Converted: $count files in " . round($time, 2) . "s\n";
echo "[ERRORS] $errors files failed\n";
