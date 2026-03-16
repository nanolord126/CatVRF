<?php

declare(strict_types=1);

/**
 * Чеккер соответствия Pages файлов канонического шаблона AutoResource
 * Проверяет обязательные компоненты: DI, authorization, logging, transactions и т.д.
 */

$pagesDir = __DIR__ . '/app/Filament/Tenant/Resources/Marketplace';
$issues = [];
$checklist = [];

// Требуемые элементы для каждого типа Page
$requiredPatterns = [
    'List' => [
        'Guard' => 'Illuminate\\Contracts\\Auth\\Guard|protected Guard',
        'LogManager' => 'Illuminate\\Log\\LogManager|protected LogManager',
        'authorizeAccess' => 'protected function authorizeAccess|public function mount',
        'viewAny check' => "allows\\('viewAny'|allows\\('view",
        'audit logging' => "log->channel\\('audit'\\)",
    ],
    'Create' => [
        'Guard' => 'Illuminate\\Contracts\\Auth\\Guard|protected Guard',
        'LogManager' => 'Illuminate\\Log\\LogManager|protected LogManager',
        'DatabaseManager' => 'Illuminate\\Database\\DatabaseManager|protected DatabaseManager',
        'RateLimiter' => 'Illuminate\\Cache\\RateLimiter|protected RateLimiter',
        'create check' => "allows\\('create'",
        'rate limiting' => 'RateLimiter|rateLimiter',
        'transaction' => 'transaction\\(|db->transaction',
        'whitelist' => 'array_intersect_key|array_only',
        'correlation_id' => 'correlation_id|X-Correlation-ID',
        'audit logging' => "log->channel\\('audit'\\)",
    ],
    'Show' => [
        'Guard' => 'Illuminate\\Contracts\\Auth\\Guard|protected Guard',
        'LogManager' => 'Illuminate\\Log\\LogManager|protected LogManager',
        'view check' => "allows\\('view'",
        'audit logging' => "log->channel\\('audit'\\)",
    ],
    'Edit' => [
        'Guard' => 'Illuminate\\Contracts\\Auth\\Guard|protected Guard',
        'LogManager' => 'Illuminate\\Log\\LogManager|protected LogManager',
        'DatabaseManager' => 'Illuminate\\Database\\DatabaseManager|protected DatabaseManager',
        'update check' => "allows\\('update'",
        'transaction' => 'transaction\\(|db->transaction',
        'whitelist' => 'array_intersect_key|array_only',
        'correlation_id' => 'correlation_id|X-Correlation-ID',
        'field tracking' => 'changed_fields|changedFields|array_diff',
        'audit logging' => "log->channel\\('audit'\\)",
    ],
];

function scanPages($dir, &$checklist, &$issues, $requiredPatterns) {
    $iterator = new RecursiveDirectoryIterator($dir);
    $recurse = new RecursiveIteratorIterator($iterator);

    foreach ($recurse as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $filename = $file->getFilename();
        $filePath = $file->getRealPath();
        $content = file_get_contents($filePath);
        $relativePath = str_replace($dir . DIRECTORY_SEPARATOR, '', $filePath);

        // Определить тип Page
        $pageType = null;
        if (strpos($filename, 'List') !== false) {
            $pageType = 'List';
        } elseif (strpos($filename, 'Create') !== false) {
            $pageType = 'Create';
        } elseif (strpos($filename, 'Show') !== false || strpos($filename, 'View') !== false) {
            $pageType = 'Show';
        } elseif (strpos($filename, 'Edit') !== false) {
            $pageType = 'Edit';
        }

        if ($pageType === null) {
            continue;
        }

        $resourceName = basename(dirname($filePath));
        $checkKey = "$resourceName/$pageType";
        $checklist[$checkKey] = [
            'file' => $relativePath,
            'status' => 'checking',
            'violations' => [],
        ];

        // Проверить требуемые паттерны
        $patterns = $requiredPatterns[$pageType] ?? [];
        foreach ($patterns as $requirement => $pattern) {
            $regex = '/' . str_replace('/', '\\/', $pattern) . '/';
            if (!preg_match($regex, $content)) {
                $checklist[$checkKey]['violations'][] = "❌ Отсутствует: $requirement";
            }
        }

        if (empty($checklist[$checkKey]['violations'])) {
            $checklist[$checkKey]['status'] = 'PASS';
        } else {
            $checklist[$checkKey]['status'] = 'FAIL';
            $issues[] = $checkKey;
        }
    }
}

// Запустить сканирование
scanPages($pagesDir, $checklist, $issues, $requiredPatterns);

// Вывести отчет
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  ЧЕККЕР СООТВЕТСТВИЯ PAGES КАНОНИЧЕСКОЙ СТРУКТУРЕ AUTOSERVICE  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$passed = 0;
$failed = 0;

ksort($checklist);
foreach ($checklist as $key => $result) {
    $status = $result['status'] === 'PASS' ? '✅' : '❌';
    echo "{$status} {$key}\n";

    if ($result['status'] === 'FAIL') {
        $failed++;
        foreach ($result['violations'] as $violation) {
            echo "   {$violation}\n";
        }
    } else {
        $passed++;
    }
}

echo "\n" . str_repeat("═", 65) . "\n";
echo "Всего: " . count($checklist) . " | ✅ Пройдено: {$passed} | ❌ Ошибки: {$failed}\n";
echo str_repeat("═", 65) . "\n";

if ($failed > 0) {
    echo "\n⚠️  ВНИМАНИЕ: Обнаружены нарушения соответствия шаблону!\n";
    exit(1);
} else {
    echo "\n✅ Все Pages соответствуют канонической структуре!\n";
    exit(0);
}
