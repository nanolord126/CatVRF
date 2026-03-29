<?php declare(strict_types=1);

/**
 * КОМПЛЕКСНЫЙ ВЪЕДЧИВЫЙ АУДИТ ВЕРТИКАЛЕЙ 2026
 * Проверка:
 * 1. Существование Resource файла для каждой вертикали
 * 2. Существование всех 4 Page типов
 * 3. Корректность связей (Resource::class указан правильно)
 * 4. CANON 2026 compliance (declare, namespace, extends, logging, tenant scoping)
 * 5. Логика вертикалей (domain-specific requirements)
 */

$resourcesBasePath = __DIR__ . '/app/Filament/Tenant/Resources';
$verticals = [];
$auditResults = [
    'total_verticals' => 0,
    'resources_ok' => 0,
    'resources_fail' => 0,
    'pages_ok' => 0,
    'pages_fail' => 0,
    'logic_errors' => [],
    'warnings' => [],
    'critical_errors' => [],
];

// Сканируем все вертикали
$dirs = scandir($resourcesBasePath);
foreach ($dirs as $dir) {
    if ($dir === '.' || $dir === '..' || !is_dir("$resourcesBasePath/$dir")) {
        continue;
    }
    $verticals[] = $dir;
}

sort($verticals);
$auditResults['total_verticals'] = count($verticals);

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║    КОМПЛЕКСНЫЙ АУДИТ ВЕРТИКАЛЕЙ И ЛОГИКИ (42 ВЕРТИКАЛЕЙ)        ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// ============================================================================
// PART 1: ПРОВЕРКА РЕСУРСОВ
// ============================================================================
echo "📋 ЧАСТЬ 1: АУДИТ РЕСУРСОВ (Resource Files)\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

foreach ($verticals as $index => $vertical) {
    $resourcePath = "$resourcesBasePath/$vertical/{$vertical}Resource.php";
    $result = [
        'vertical' => $vertical,
        'resource_exists' => false,
        'resource_valid' => false,
        'issues' => [],
    ];

    if (!file_exists($resourcePath)) {
        $result['issues'][] = "❌ Resource file not found: {$vertical}Resource.php";
        $auditResults['resources_fail']++;
    } else {
        $content = file_get_contents($resourcePath);
        $lines = explode("\n", $content);
        $lineCount = count($lines);

        $result['resource_exists'] = true;

        // Проверка 1: declare(strict_types=1)
        if (!str_contains($content, 'declare(strict_types=1)')) {
            $result['issues'][] = "❌ Missing declare(strict_types=1)";
        }

        // Проверка 2: Правильный namespace
        if (!preg_match("/namespace App\\\\Filament\\\\Tenant\\\\Resources\\\\$vertical;/", $content)) {
            $result['issues'][] = "❌ Incorrect namespace";
        }

        // Проверка 3: Класс расширяет Resource
        if (!preg_match('/class\s+' . $vertical . 'Resource\s+extends\s+(Resource|CanAccess)/', $content)) {
            $result['issues'][] = "❌ Resource class does not extend Resource";
        }

        // Проверка 4: Установлена модель
        if (!preg_match('/protected\s+static\s+\?string\s+\$model/', $content)) {
            $result['issues'][] = "❌ Missing \\$model property";
        }

        // Проверка 5: Методы form() и table()
        if (!str_contains($content, 'public static function form')) {
            $result['issues'][] = "❌ Missing form() method";
        }
        if (!str_contains($content, 'public static function table')) {
            $result['issues'][] = "❌ Missing table() method";
        }

        // Проверка 6: getEloquentQuery() с tenant scoping
        if (!str_contains($content, 'getEloquentQuery')) {
            $result['issues'][] = "⚠️  Missing getEloquentQuery() method (should have tenant scoping)";
        }

        // Проверка 7: Hidden fields
        if (!str_contains($content, 'Hidden::make(\'tenant_id\')') && !str_contains($content, 'Hidden::make(\'correlation_id\')')) {
            $result['issues'][] = "⚠️  Missing Hidden fields (tenant_id, correlation_id)";
        }

        // Проверка 8: getPages()
        if (!str_contains($content, 'getPages()')) {
            $result['issues'][] = "⚠️  Missing getPages() method";
        }

        // Проверка 9: Форма >= 160 строк
        if ($lineCount < 160) {
            $result['issues'][] = "⚠️  Resource file too short ($lineCount lines < 160)";
        }

        // Проверка 10: Таблица >= 10 колонок
        $tableMatches = [];
        preg_match_all('/Tables\\\\Columns\\\\[A-Za-z]+::make/', $content, $tableMatches);
        $columnCount = count($tableMatches[0]);
        if ($columnCount < 10) {
            $result['issues'][] = "⚠️  Table has only $columnCount columns (< 10 minimum)";
        }

        if (empty($result['issues'])) {
            $result['resource_valid'] = true;
            $auditResults['resources_ok']++;
        } else {
            $auditResults['resources_fail']++;
        }
    }

    // Вывод
    $status = $result['resource_valid'] ? '✅' : '⚠️ ';
    echo "$status [$index+1/42] {$vertical}Resource\n";
    if (!empty($result['issues'])) {
        foreach ($result['issues'] as $issue) {
            echo "     $issue\n";
        }
    }
}

// ============================================================================
// PART 2: ПРОВЕРКА PAGES
// ============================================================================
echo "\n📄 ЧАСТЬ 2: АУДИТ PAGES (Page Files)\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$pageTypes = ['List', 'Create', 'Edit', 'View'];
$pageTypeMapping = [
    'List' => 'ListRecords',
    'Create' => 'CreateRecord',
    'Edit' => 'EditRecord',
    'View' => 'ViewRecord',
];

foreach ($verticals as $verticalIndex => $vertical) {
    echo "\n🔹 {$vertical} VERTICAL\n";
    $pagesDir = "$resourcesBasePath/$vertical/Pages";
    $verticalPageStatus = ['found' => 0, 'missing' => 0, 'invalid' => 0];

    foreach ($pageTypes as $pageType) {
        // Определяем имя файла
        $singleName = rtrim($vertical, 's'); // beauty -> beauty, beauties -> beautie
        if (str_ends_with($vertical, 'ies')) {
            $singleName = substr($vertical, 0, -3) . 'y'; // beauties -> beauty
        }

        $possibleNames = [
            $pageType . $vertical,         // ListBeauties
            $pageType . $singleName,       // ListBeauty
            $pageType . rtrim($vertical, 's'), // ListBeauty (if plural)
        ];

        $pageFile = null;
        foreach ($possibleNames as $name) {
            $path = "$pagesDir/{$name}.php";
            if (file_exists($path)) {
                $pageFile = $path;
                break;
            }
        }

        if (!$pageFile) {
            echo "  ❌ Missing: {$pageType}* page\n";
            $verticalPageStatus['missing']++;
            $auditResults['pages_fail']++;
            continue;
        }

        $content = file_get_contents($pageFile);
        $lines = explode("\n", $content);
        $lineCount = count($lines);
        $fileName = basename($pageFile);

        $pageIssues = [];

        // Проверка 1: declare(strict_types=1)
        if (!str_contains($content, 'declare(strict_types=1)')) {
            $pageIssues[] = 'Missing declare(strict_types=1)';
        }

        // Проверка 2: Правильный namespace
        if (!preg_match("/namespace App\\\\Filament\\\\Tenant\\\\Resources\\\\$vertical\\\\Pages/", $content)) {
            $pageIssues[] = 'Incorrect namespace';
        }

        // Проверка 3: Правильное наследование
        $expectedExtends = $pageTypeMapping[$pageType];
        if (!str_contains($content, "extends $expectedExtends")) {
            $pageIssues[] = "Should extend $expectedExtends";
        }

        // Проверка 4: Минимум 70 строк
        if ($lineCount < 70) {
            $pageIssues[] = "Too short ($lineCount lines < 70)";
        }

        // Проверка 5: Log::channel('audit')
        if (!str_contains($content, "Log::channel('audit')")) {
            $pageIssues[] = "Missing Log::channel('audit')";
        }

        // Проверка 6: Resource::class указан правильно
        if (!preg_match("/{$vertical}Resource::class/", $content)) {
            $pageIssues[] = "{$vertical}Resource::class reference missing or incorrect";
        }

        // Проверка 7: Tenant scoping (для List и View)
        if ($pageType === 'List' || $pageType === 'View') {
            if (!str_contains($content, 'filament()->getTenant()->id') && !str_contains($content, 'tenant_id')) {
                $pageIssues[] = 'Missing tenant scoping';
            }
        }

        // Проверка 8: Soft delete (для List)
        if ($pageType === 'List') {
            if (!str_contains($content, 'whereNull(\'deleted_at\')')) {
                $pageIssues[] = 'Missing whereNull(deleted_at)';
            }
        }

        // Проверка 9: DB::transaction() (для Create/Edit)
        if ($pageType === 'Create' || $pageType === 'Edit') {
            if (!str_contains($content, 'DB::transaction')) {
                $pageIssues[] = 'Missing DB::transaction wrapper';
            }
        }

        // Вывод
        if (empty($pageIssues)) {
            echo "  ✅ {$pageType}* ($fileName, $lineCount lines)\n";
            $verticalPageStatus['found']++;
            $auditResults['pages_ok']++;
        } else {
            echo "  ⚠️  {$pageType}* ($fileName, $lineCount lines)\n";
            foreach ($pageIssues as $issue) {
                echo "       - $issue\n";
            }
            $verticalPageStatus['invalid']++;
            $auditResults['pages_fail']++;
        }
    }

    echo "   📊 Summary: {$verticalPageStatus['found']}/4 OK, {$verticalPageStatus['missing']} missing, {$verticalPageStatus['invalid']} invalid\n";
}

// ============================================================================
// PART 3: ЛОГИКА СВЯЗЕЙ
// ============================================================================
echo "\n\n🔗 ЧАСТЬ 3: ПРОВЕРКА ЛОГИКИ СВЯЗЕЙ\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

foreach ($verticals as $vertical) {
    $resourcePath = "$resourcesBasePath/$vertical/{$vertical}Resource.php";
    $pagesDir = "$resourcesBasePath/$vertical/Pages";

    if (!file_exists($resourcePath)) {
        $auditResults['logic_errors'][] = "$vertical: Resource file missing";
        continue;
    }

    $resourceContent = file_get_contents($resourcePath);

    // Проверка 1: getPages() возвращает страницы
    if (preg_match('/public\s+static\s+function\s+getPages\(\)[^{]*{([^}]+)}/', $resourceContent, $matches)) {
        $pagesBody = $matches[1];
        $expectedPages = [
            'index' => 'ListRecords',
            'create' => 'CreateRecord',
            'edit' => 'EditRecord',
            'view' => 'ViewRecord',
        ];

        foreach ($expectedPages as $key => $class) {
            if (!str_contains($pagesBody, $key) || !str_contains($pagesBody, $class)) {
                $auditResults['warnings'][] = "$vertical: getPages() missing $key => $class mapping";
            }
        }
    } else {
        $auditResults['warnings'][] = "$vertical: getPages() method structure unclear";
    }

    // Проверка 2: Pages существуют
    $pagesRequired = 4;
    $pagesFound = 0;
    if (is_dir($pagesDir)) {
        $files = array_diff(scandir($pagesDir), ['.', '..']);
        $pagesFound = count(array_filter($files, fn($f) => str_ends_with($f, '.php')));
    }

    if ($pagesFound < $pagesRequired) {
        $auditResults['logic_errors'][] = "$vertical: Only $pagesFound/$pagesRequired Pages found";
    }

    // Проверка 3: Resource имеет модель
    if (!preg_match('/\$model\s*=\s*[A-Za-z0-9\\\\]+::class/', $resourceContent)) {
        $auditResults['critical_errors'][] = "$vertical: No model defined in Resource";
    }
}

// ============================================================================
// PART 4: ФИНАЛЬНЫЙ ОТЧЕТ
// ============================================================================
echo "\n\n═══════════════════════════════════════════════════════════════════\n";
echo "📊 ФИНАЛЬНЫЙ ОТЧЕТ КОМПЛЕКСНОГО АУДИТА\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "📈 СТАТИСТИКА ПО РЕСУРСАМ:\n";
echo "├─ Всего вертикалей: {$auditResults['total_verticals']}\n";
echo "├─ Resource ОК: {$auditResults['resources_ok']}\n";
echo "├─ Resource ОШИБКИ: {$auditResults['resources_fail']}\n";
echo "└─ Compliance: " . number_format(100 * $auditResults['resources_ok'] / $auditResults['total_verticals'], 1) . "%\n\n";

echo "📄 СТАТИСТИКА ПО PAGES:\n";
echo "├─ Pages ОК: {$auditResults['pages_ok']}\n";
echo "├─ Pages ОШИБКИ: {$auditResults['pages_fail']}\n";
$totalPages = $auditResults['pages_ok'] + $auditResults['pages_fail'];
echo "└─ Compliance: " . ($totalPages > 0 ? number_format(100 * $auditResults['pages_ok'] / $totalPages, 1) : '0') . "%\n\n";

if (!empty($auditResults['critical_errors'])) {
    echo "🚨 КРИТИЧЕСКИЕ ОШИБКИ (" . count($auditResults['critical_errors']) . "):\n";
    foreach ($auditResults['critical_errors'] as $error) {
        echo "  ❌ $error\n";
    }
    echo "\n";
}

if (!empty($auditResults['logic_errors'])) {
    echo "⚠️  ЛОГИЧЕСКИЕ ОШИБКИ (" . count($auditResults['logic_errors']) . "):\n";
    foreach ($auditResults['logic_errors'] as $error) {
        echo "  ⚠️  $error\n";
    }
    echo "\n";
}

if (!empty($auditResults['warnings'])) {
    echo "ℹ️  ПРЕДУПРЕЖДЕНИЯ (" . count($auditResults['warnings']) . "):\n";
    foreach (array_slice($auditResults['warnings'], 0, 10) as $warning) {
        echo "  ℹ️  $warning\n";
    }
    if (count($auditResults['warnings']) > 10) {
        echo "  ... и ещё " . (count($auditResults['warnings']) - 10) . " предупреждений\n";
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════════\n";

$totalCompliance = ($auditResults['resources_ok'] + $auditResults['pages_ok']) / 
                   ($auditResults['total_verticals'] * 4 + $auditResults['total_verticals']);

if ($auditResults['resources_fail'] === 0 && $auditResults['pages_fail'] === 0) {
    echo "✅ ВСЕ ВЕРТИКАЛИ И PAGES ГОТОВЫ К DEPLOYMENT!\n";
    echo "   📊 Общий Compliance: " . number_format(100 * $totalCompliance, 1) . "%\n";
} else {
    echo "⚠️  ТРЕБУЮТСЯ ИСПРАВЛЕНИЯ\n";
    echo "   📊 Общий Compliance: " . number_format(100 * $totalCompliance, 1) . "%\n";
    echo "   💡 Исправьте ошибки выше и перезапустите аудит\n";
}

echo "\n📅 Дата аудита: " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
