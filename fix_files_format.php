<?php

/*
 * Fix ALL PHP files: UTF-8 no BOM + CRLF line endings
 * CRITICAL: Ensures production-ready file format
 */

$projectRoot = getcwd();
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $projectRoot,
        RecursiveDirectoryIterator::SKIP_DOTS
    )
);

$phpFiles = new RegexIterator($iterator, '/\.php$/i');

$converted = 0;
$errors = 0;
$total = 0;

foreach ($phpFiles as $file) {
    $total++;
    $filePath = $file->getRealPath();

    try {
        // Read file as binary
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new Exception("Cannot read file");
        }

        // Remove BOM if exists (UTF-8 BOM is bytes: EF BB BF)
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        // Normalize line endings
        // First convert all CRLF to LF, then CR to LF, then LF to CRLF
        $content = str_replace("\r\n", "\n", $content);  // CRLF -> LF
        $content = str_replace("\r", "\n", $content);    // CR -> LF
        $content = str_replace("\n", "\r\n", $content);  // LF -> CRLF

        // Ensure UTF-8 encoding (convert if needed)
        $encoding = mb_detect_encoding($content, 'UTF-8,ISO-8859-1,CP1252', true);
        if ($encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        // Write back as UTF-8 WITHOUT BOM
        if (file_put_contents($filePath, $content, FILE_BINARY) === false) {
            throw new Exception("Cannot write file");
        }

        $converted++;

        if ($converted % 500 === 0) {
            echo "[✓] Processed: $converted files...\n";
        }
    } catch (Exception $e) {
        echo "[✗] ERROR in " . basename($filePath) . ": " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n";
echo "=== CONVERSION COMPLETE ===\n";
echo "Converted: $converted files\n";
echo "Errors: $errors files\n";
echo "Total: $total files processed\n";

// Verify a sample file
echo "\n=== VERIFICATION ===\n";
$sampleFile = 'app/Events/EmployeeRestored.php';
if (file_exists($sampleFile)) {
    $bytes = file_get_contents($sampleFile);
    $hasBom = substr($bytes, 0, 3) === "\xEF\xBB\xBF";
    $hasCrlf = strpos($bytes, "\r\n") !== false;
    echo "Sample: $sampleFile\n";
    echo "  BOM: " . ($hasBom ? "YES (WRONG!)" : "NO (OK)") . "\n";
    echo "  CRLF: " . ($hasCrlf ? "YES (OK)" : "NO (WRONG!)") . "\n";
}
