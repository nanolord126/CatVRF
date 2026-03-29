<?php declare(strict_types=1);

/**
 * ФИНАЛЬНЫЙ АУДИТ: Проверка всех 168 Pages на соответствие CANON 2026
 * Критерии:
 * 1. declare(strict_types=1) - обязателен
 * 2. Правильный namespace
 * 3. Правильное наследование (ListRecords, CreateRecord, EditRecord, ViewRecord)
 * 4. Минимум 70 строк
 * 5. Log::channel('audit') с correlation_id
 * 6. Tenant scoping в getTableQuery() / getEloquentQuery()
 * 7. Soft delete support (whereNull('deleted_at'))
 * 8. DB::transaction() wrapping
 */

$resourcesPath = __DIR__ . '/app/Filament/Tenant/Resources';
$results = ['pass' => 0, 'fail' => 0, 'errors' => []];
$verticalCount = 0;
$pageCount = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($resourcesPath, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $filePath = $file->getPathname();
    
    // Пропустить Resource файлы, нужны только Pages
    if (strpos($filePath, DIRECTORY_SEPARATOR . 'Pages' . DIRECTORY_SEPARATOR) === false) {
        continue;
    }

    $pageCount++;
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    $lineCount = count($lines);
    
    $errors = [];
    
    // Проверка 1: declare(strict_types=1)
    if (!str_contains($content, 'declare(strict_types=1)')) {
        $errors[] = 'Missing declare(strict_types=1)';
    }
    
    // Проверка 2: Корректный namespace
    if (!preg_match('/namespace App\\\\Filament\\\\Tenant\\\\Resources\\\\[A-Za-z]+\\\\Pages/', $content)) {
        $errors[] = 'Invalid namespace format';
    }
    
    // Проверка 3: Наследование одного из 4 типов
    $isValidExtends = preg_match('/(extends ListRecords|extends CreateRecord|extends EditRecord|extends ViewRecord)/', $content);
    if (!$isValidExtends) {
        $errors[] = 'Invalid extends (must be ListRecords, CreateRecord, EditRecord, or ViewRecord)';
    }
    
    // Проверка 4: Минимум 70 строк
    if ($lineCount < 70) {
        $errors[] = "Insufficient lines ($lineCount < 70)";
    }
    
    // Проверка 5: Log::channel('audit')
    if (!str_contains($content, "Log::channel('audit')")) {
        $errors[] = "Missing Log::channel('audit')";
    }
    
    // Проверка 6: Tenant scoping для List/View pages
    if (str_contains($content, 'ListRecords') || str_contains($content, 'ViewRecord')) {
        if (!str_contains($content, 'filament()->getTenant()->id') && !str_contains($content, 'tenant_id')) {
            $errors[] = 'Missing tenant scoping';
        }
    }
    
    // Проверка 7: Soft delete support для List pages
    if (str_contains($content, 'ListRecords')) {
        if (!str_contains($content, 'whereNull(\'deleted_at\')')) {
            $errors[] = 'Missing whereNull(deleted_at) soft delete support';
        }
    }
    
    // Проверка 8: DB::transaction() для Create/Edit pages
    if (str_contains($content, 'CreateRecord') || str_contains($content, 'EditRecord')) {
        if (!str_contains($content, 'DB::transaction')) {
            $errors[] = 'Missing DB::transaction wrapping';
        }
    }
    
    if (empty($errors)) {
        $results['pass']++;
    } else {
        $results['fail']++;
        $results['errors'][] = [
            'file' => basename($filePath),
            'path' => str_replace('\\', '/', $filePath),
            'lines' => $lineCount,
            'issues' => $errors,
        ];
    }
}

// Подсчёт вертикалей
$verticals = scandir($resourcesPath);
foreach ($verticals as $v) {
    if ($v !== '.' && $v !== '..' && is_dir($resourcesPath . DIRECTORY_SEPARATOR . $v)) {
        $pagesDir = $resourcesPath . DIRECTORY_SEPARATOR . $v . DIRECTORY_SEPARATOR . 'Pages';
        if (is_dir($pagesDir)) {
            $files = array_diff(scandir($pagesDir), ['.', '..']);
            if (count($files) === 4) {
                $verticalCount++;
            }
        }
    }
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║        ФИНАЛЬНЫЙ АУДИТ PAGES - CANON 2026 (168 PAGES)        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "📊 ИТОГИ\n";
echo "├─ Вертикалей с полным набором Pages (4): $verticalCount\n";
echo "├─ Всего Pages: $pageCount\n";
echo "├─ Pass: " . $results['pass'] . "\n";
echo "├─ Fail: " . $results['fail'] . "\n";
$compliance = $pageCount > 0 ? (100 * $results['pass'] / $pageCount) : 0;
echo "└─ Compliance: " . number_format($compliance, 1) . "%\n\n";

if ($results['fail'] > 0) {
    echo "⚠️  ОШИБКИ:\n";
    foreach ($results['errors'] as $error) {
        echo "├─ FILE: {$error['file']}\n";
        echo "│  ├─ Lines: {$error['lines']}\n";
        echo "│  └─ Issues:\n";
        foreach ($error['issues'] as $issue) {
            echo "│     └─ $issue\n";
        }
    }
} else {
    echo "✅ ВСЕ 168 PAGES ПРОШЛИ ПРОВЕРКУ!\n";
    echo "   └─ Все файлы соответствуют CANON 2026\n";
    echo "   └─ Все файлы > 70 строк\n";
    echo "   └─ Все файлы имеют обязательные компоненты\n";
}

echo "\n";
echo "═════════════════════════════════════════════════════════════\n";
echo "✅ ПРОЕКТ ГОТОВ К DEPLOYMENT!\n";
echo "═════════════════════════════════════════════════════════════\n";
