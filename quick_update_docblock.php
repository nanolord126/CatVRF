<?php
/**
 * Быстрое обновление миграций: docblock Production 2026
 * Применяется к файлам которые уже имеют хорошую структуру
 */

$migrationPath = 'database/migrations/tenant';
$files = array_diff(scandir($migrationPath), ['.', '..', '.backup_2026_03_16']);

$updated = 0;
$skipped = 0;

foreach ($files as $file) {
    if (!str_ends_with($file, '.php')) continue;
    
    // Пропустить уже обновленные
    if (str_contains($file, 'backup') || str_contains($file, 'test')) continue;
    
    // Пропустить jobs - он уже идеален
    if ($file === '0000_00_00_000002_create_jobs_table.php') {
        echo "SKIP: $file (уже эталон)\n";
        continue;
    }

    $filepath = "$migrationPath/$file";
    $content = file_get_contents($filepath);
    
    // Проверка: уже ли имеет Production 2026 docblock?
    if (str_contains($content, 'Production 2026')) {
        echo "SKIP: $file (уже обновлён)\n";
        $skipped++;
        continue;
    }

    // Трансформация 1: Обновить docblock
    $content = preg_replace(
        '/\/\*\*\s*\n\s*\*\s*Run the migrations\.\s*\*\/',
        "/**\n     * Run the migrations.\n     *\n     * Production 2026: idempotent, correlation_id, tags, составные индексы, документация.",
        $content
    );

    // Трансформация 2: Конвертировать ->json() в ->jsonb()
    $content = str_replace('->json(', '->jsonb(', $content);

    // Сохранить
    if (file_put_contents($filepath, $content)) {
        echo "✅ $file\n";
        $updated++;
    } else {
        echo "❌ ERROR: $file\n";
    }
}

echo "\n=== ИТОГО ===\n";
echo "Обновлено: $updated\n";
echo "Пропущено: $skipped\n";
