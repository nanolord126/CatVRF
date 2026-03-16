<?php

declare(strict_types=1);

$basePath = __DIR__ . '/app/Filament/Tenant/Resources';
$adminPath = __DIR__ . '/app/Filament/Admin/Resources';

$issues = [];
$fileCount = 0;
$dirCount = 0;

function auditPages($basePath, &$issues, &$fileCount, &$dirCount)
{
    if (!is_dir($basePath)) {
        return;
    }

    $iterator = new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS);
    $recursiveIterator = new RecursiveIteratorIterator($iterator);
    $phpFiles = new RegexIterator($recursiveIterator, '/^.+\.php$/i');

    foreach ($phpFiles as $file) {
        $path = $file->getPathname();
        if (strpos($path, '/Pages/') === false && strpos($path, '\\Pages\\') === false) {
            continue;
        }

        $dirCount++;
        $content = file_get_contents($path);
        $relativePath = str_replace(__DIR__ . '/', '', $path);

        $fileCount++;

        // Проверка 1: Пустой файл
        if (trim($content) === '') {
            $issues[] = "[$relativePath] EMPTY FILE";
            continue;
        }

        // Проверка 2: Имеет $view вместо правильной реализации (разрешить для кастомных Pages)
        if (preg_match('/protected\s+static\s+string\s+\$view/', $content) &&
            !preg_match('/Kanban|Custom|Dashboard/', $content)) {
            $issues[] = "[$relativePath] STUB: uses \$view instead of proper implementation";
        }

        // Проверка 3: Нет use statements для Filament
        if (!preg_match('/use\s+Filament\\\\Resources\\\\Pages/', $content)) {
            $issues[] = "[$relativePath] MISSING: Filament Pages use statement";
        }

        // Проверка 4: Не extends правильный класс
        if (!preg_match('/extends\s+(ListRecords|CreateRecord|EditRecord|ViewRecord|Page)/', $content)) {
            $issues[] = "[$relativePath] MISSING: proper extends clause";
        }

        // Проверка 5: Нет namespace
        if (!preg_match('/namespace\s+App\\\\Filament/', $content)) {
            $issues[] = "[$relativePath] INVALID: namespace doesn't match Filament structure";
        }

        // Проверка 6: Содержит {PLACEHOLDER}
        if (preg_match('/\{[A-Z_]+\}/', $content)) {
            $issues[] = "[$relativePath] TEMPLATE: contains placeholder variables like {NAMESPACE}";
        }

        // Проверка 7: Содержит TODO или FIXME
        if (preg_match('/(TODO|FIXME|XXX|HACK)/', $content)) {
            $issues[] = "[$relativePath] TODO: contains unfinished comments";
        }

        // Проверка 8: У Declaration нет $resource
        if (preg_match('/class\s+\w+\s+extends\s+(ListRecords|CreateRecord|EditRecord|ViewRecord)/', $content) &&
            !preg_match('/protected\s+static\s+string\s+\$resource/', $content)) {
            $issues[] = "[$relativePath] MISSING: \$resource static property";
        }

        // Проверка 9: Неправильный BOM/Encoding
        $bom = substr($content, 0, 3);
        if ($bom === pack('H*', 'EFBBBF')) {
            $issues[] = "[$relativePath] ENCODING: has UTF-8 BOM";
        }

        // Проверка 10: Неправильные line endings
        if (strpos($content, "\r\n") === false && strpos($content, "\n") !== false) {
            $issues[] = "[$relativePath] LINE ENDINGS: LF instead of CRLF";
        }
    }
}

echo "Scanning Tenant Resources...\n";
auditPages($basePath, $issues, $fileCount, $dirCount);

echo "Scanning Admin Resources...\n";
auditPages($adminPath, $issues, $fileCount, $dirCount);

echo "\n=== AUDIT RESULTS ===\n";
echo "Total Pages directories found: $dirCount\n";
echo "Total PHP files in Pages: $fileCount\n";
echo "Issues found: " . count($issues) . "\n\n";

if (!empty($issues)) {
    echo "=== DETAILED ISSUES ===\n";
    foreach ($issues as $issue) {
        echo "$issue\n";
    }
} else {
    echo "✓ All Pages are production-ready!\n";
}
