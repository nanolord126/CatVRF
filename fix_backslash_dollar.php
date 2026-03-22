<?php
/**
 * Исправляет FraudControlService \$fraudControlService → $fraudControlService
 * во всех PHP-файлах проекта
 */
$baseDir = __DIR__ . '/app';
$fixed = 0;
$checked = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $content = file_get_contents($path);
    $checked++;

    // Ищем паттерн: FraudControlService \$fraudControlService
    // (литеральный backslash + знак доллара)
    if (str_contains($content, "FraudControlService \\$fraudControlService")) {
        $newContent = str_replace(
            "FraudControlService \\$fraudControlService",
            "FraudControlService \$fraudControlService",
            $content
        );
        file_put_contents($path, $newContent);
        $fixed++;
        echo "FIXED: $path\n";
    }
}

echo "\n--- ИТОГО ---\n";
echo "Проверено: $checked файлов\n";
echo "Исправлено: $fixed файлов\n";
