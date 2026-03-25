<?php declare(strict_types=1);

/**
 * PHASE 3: Models Audit & Enhancement
 * Проверяет наличие обязательных полей в моделях согласно КАНОНУ 2026
 * 
 * Обязательные поля:
 * - uuid
 * - correlation_id  
 * - tags (jsonb)
 * - business_group_id (nullable)
 * - booted() метод с tenant scoping
 */

$basePath = 'c:\\opt\\kotvrf\\CatVRF';
$modelsPath = $basePath . '\\app\\Domains';

$stats = [
    'total_models' => 0,
    'missing_uuid' => [],
    'missing_correlation_id' => [],
    'missing_tags' => [],
    'missing_business_group_id' => [],
    'missing_booted' => [],
    'ok_models' => 0,
];

echo "════════════════════════════════════════════════════════════════\n";
echo "🔍 ФАЗА 3: АУДИТ МОДЕЛЕЙ\n";
echo "════════════════════════════════════════════════════════════════\n\n";

function checkModel($filePath, &$stats) {
    $content = file_get_contents($filePath);
    $filename = basename($filePath);
    
    $stats['total_models']++;
    $problems = [];
    
    // Проверка 1: uuid в $fillable или $appends
    if (!str_contains($content, "'uuid'") && !str_contains($content, '"uuid"')) {
        $problems[] = 'uuid';
        $stats['missing_uuid'][] = $filename;
    }
    
    // Проверка 2: correlation_id
    if (!str_contains($content, "'correlation_id'") && !str_contains($content, '"correlation_id"')) {
        $problems[] = 'correlation_id';
        $stats['missing_correlation_id'][] = $filename;
    }
    
    // Проверка 3: tags (jsonb)
    if (!str_contains($content, "'tags'") && !str_contains($content, '"tags"')) {
        $problems[] = 'tags';
        $stats['missing_tags'][] = $filename;
    }
    
    // Проверка 4: business_group_id
    if (!str_contains($content, "'business_group_id'") && !str_contains($content, '"business_group_id"')) {
        $problems[] = 'business_group_id';
        $stats['missing_business_group_id'][] = $filename;
    }
    
    // Проверка 5: booted() метод
    if (!str_contains($content, 'protected static function booted()') && 
        !str_contains($content, 'public static function booted()')) {
        $problems[] = 'booted()';
        $stats['missing_booted'][] = $filename;
    }
    
    if (empty($problems)) {
        $stats['ok_models']++;
    }
    
    return $problems;
}

// Рекурсивный поиск всех Model файлов
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modelsPath),
    RecursiveIteratorIterator::SELF_FIRST
);

$count = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getPathname(), '.php')) {
        $problems = checkModel($file->getPathname(), $stats);
        if (!empty($problems) && $count < 10) {  // Показать первые 10 проблемных моделей
            echo "⚠️  " . $file->getFilename() . "\n";
            foreach ($problems as $p) {
                echo "    ❌ Отсутствует: $p\n";
            }
            $count++;
        }
    }
}

// РЕЗЮМЕ
echo "\n════════════════════════════════════════════════════════════════\n";
echo "📊 ИТОГИ АУДИТА МОДЕЛЕЙ\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "📈 Всего моделей: " . $stats['total_models'] . "\n";
echo "✅ Готовых моделей: " . $stats['ok_models'] . "\n";
echo "❌ Требуют обновления: " . ($stats['total_models'] - $stats['ok_models']) . "\n\n";

echo "🔴 КРИТИЧНЫЕ ПРОБЕЛЫ:\n";
echo "  • Отсутствует uuid: " . count($stats['missing_uuid']) . " моделей\n";
echo "  • Отсутствует correlation_id: " . count($stats['missing_correlation_id']) . " моделей\n";
echo "  • Отсутствует tags: " . count($stats['missing_tags']) . " моделей\n";
echo "  • Отсутствует business_group_id: " . count($stats['missing_business_group_id']) . " моделей\n";
echo "  • Отсутствует booted(): " . count($stats['missing_booted']) . " моделей\n\n";

echo "💡 ПЛАН ДЕЙСТВИЙ:\n";
echo "  1. Создать миграцию для добавления uuid, correlation_id, tags, business_group_id\n";
echo "  2. Добавить поля в $fillable каждой модели\n";
echo "  3. Добавить booted() метод с TenantScope и BusinessGroupScope\n";
echo "  4. Запустить миграцию: php artisan migrate\n\n";

// Сохранить отчёт
file_put_contents(
    $basePath . '\\PHASE3_MODEL_AUDIT_' . date('Y-m-d_His') . '.json',
    json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo "✅ Детальный отчёт сохранён в PHASE3_MODEL_AUDIT_*.json\n";
