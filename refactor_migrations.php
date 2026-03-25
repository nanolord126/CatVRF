<?php
/**
 * Автоматизированный рефакторинг миграций до Production 2026 уровня
 * Эталон: database/migrations/tenant/0000_00_00_000002_create_jobs_table.php
 * 
 * Требования:
 * 1. Идempotent checks: if (!Schema::hasTable(...))
 * 2. Комментарии к таблицам: $table->comment('...')
 * 3. Комментарии к полям: ->comment('...')
 * 4. uuid: ->uuid()->nullable()->unique()->index()
 * 5. correlation_id: ->string('correlation_id')->nullable()->index()
 * 6. tags: ->jsonb('tags')->nullable()
 * 7. Составные индексы
 * 8. Production 2026 документация
 */

class MigrationRefactor
{
    private string $migrationsPath;
    private array $report = [
        'total' => 0,
        'processed' => 0,
        'skipped' => 0,
        'errors' => [],
        'files' => []
    ];

    public function __construct(string $path = 'database/migrations/tenant')
    {
        $this->migrationsPath = $path;
    }

    /**
     * Главный метод: проанализировать все миграции
     */
    public function analyze(): array
    {
        $files = $this->getMigrationFiles();
        $this->report['total'] = count($files);

        foreach ($files as $file) {
            $this->analyzeFile($file);
        }

        return $this->report;
    }

    /**
     * Получить все файлы миграций
     */
    private function getMigrationFiles(): array
    {
        $path = getcwd() . '/' . $this->migrationsPath;
        if (!is_dir($path)) {
            return [];
        }

        $files = array_diff(scandir($path), ['.', '..']);
        $phpFiles = array_filter($files, fn($f) => str_ends_with($f, '.php'));
        
        return array_values($phpFiles);
    }

    /**
     * Анализ одного файла
     */
    private function analyzeFile(string $filename): void
    {
        $filepath = getcwd() . '/' . $this->migrationsPath . '/' . $filename;
        
        if (!is_file($filepath)) {
            return;
        }

        $content = file_get_contents($filepath);
        
        // Анализ структуры
        $analysis = [
            'file' => $filename,
            'hasIdempotent' => (bool)preg_match('/if\s*\(\s*!\s*Schema::hasTable/', $content),
            'hasTableComment' => (bool)preg_match('/\$table->comment\(/', $content),
            'hasUuid' => (bool)preg_match('/->uuid\(\)/', $content),
            'hasCorrelationId' => (bool)preg_match('/correlation_id/', $content),
            'hasJsonb' => (bool)preg_match('/->jsonb\(/', $content),
            'hasJson' => (bool)preg_match('/->json\(/', $content),
            'hasTags' => preg_match("/tags/i", $content) > 0,
            'tables' => $this->extractTableNames($content),
            'hasDocblock' => (bool)preg_match('/\/\*\*.*?Production.*?\*\//s', $content),
            'needsRefactoring' => [],
        ];

        // Определить что нужно рефакторить
        if (!$analysis['hasIdempotent']) {
            $analysis['needsRefactoring'][] = 'idempotent_checks';
        }
        if (!$analysis['hasTableComment']) {
            $analysis['needsRefactoring'][] = 'table_comments';
        }
        if (!$analysis['hasCorrelationId'] && $this->shouldHaveCorrelationId($filename, $analysis)) {
            $analysis['needsRefactoring'][] = 'correlation_id';
        }
        if ($analysis['hasJson'] && !$analysis['hasJsonb']) {
            $analysis['needsRefactoring'][] = 'json_to_jsonb';
        }
        if (!$analysis['hasTags'] && $this->shouldHaveTags($filename)) {
            $analysis['needsRefactoring'][] = 'tags_field';
        }
        if (!$analysis['hasDocblock']) {
            $analysis['needsRefactoring'][] = 'production_docblock';
        }

        $this->report['files'][$filename] = $analysis;
        
        if (!empty($analysis['needsRefactoring'])) {
            $this->report['processed']++;
        } else {
            $this->report['skipped']++;
        }
    }

    /**
     * Извлечь имена таблиц из файла
     */
    private function extractTableNames(string $content): array
    {
        $tables = [];
        if (preg_match_all("/Schema::create\(['\"](.*?)['\"]/", $content, $matches)) {
            $tables = $matches[1];
        }
        return $tables;
    }

    /**
     * Должна ли таблица иметь correlation_id?
     */
    private function shouldHaveCorrelationId(string $filename, array $analysis): bool
    {
        // Системные таблицы без correlation_id: password_resets, sessions, cache
        $skipPatterns = ['password_reset', 'session', 'cache', 'failed_job'];
        
        foreach ($skipPatterns as $pattern) {
            if (stripos($filename, $pattern) !== false) {
                return false;
            }
        }

        // Таблицы с данными должны иметь correlation_id
        return !empty($analysis['tables']);
    }

    /**
     * Должна ли таблица иметь tags field?
     */
    private function shouldHaveTags(string $filename): bool
    {
        // Все таблицы данных должны иметь tags для аналитики
        $skipPatterns = ['password_reset', 'session', 'cache', 'pivot'];
        
        foreach ($skipPatterns as $pattern) {
            if (stripos($filename, $pattern) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Рефакторинг файла
     */
    public function refactorFile(string $filename): bool
    {
        $filepath = getcwd() . '/' . $this->migrationsPath . '/' . $filename;
        
        if (!is_file($filepath)) {
            $this->report['errors'][] = "File not found: $filename";
            return false;
        }

        $content = file_get_contents($filepath);
        
        // Применить трансформации по порядку
        $content = $this->addIdempotentChecks($content);
        $content = $this->addTableComments($content);
        $content = $this->addCorrelationId($content);
        $content = $this->convertJsonToJsonb($content);
        $content = $this->addTags($content);
        $content = $this->addProductionDocblock($content);

        // Написать обратно
        if (file_put_contents($filepath, $content) === false) {
            $this->report['errors'][] = "Cannot write file: $filename";
            return false;
        }

        return true;
    }

    /**
     * Добавить идempotent проверки
     */
    private function addIdempotentChecks(string $content): string
    {
        // Найти все Schema::create() и обернуть их в if (!Schema::hasTable(...))
        
        // Паттерн: Schema::create('table_name', function (...) { ... });
        $pattern = '/Schema::create\([\'"](\w+)[\'"]\s*,\s*(?:function|static\s+function)\s*\(\s*Blueprint\s+\$table\s*\)\s*\{/';
        
        $replacement = function($matches) {
            $tableName = $matches[1];
            return "if (!Schema::hasTable('{$tableName}')) {\n            Schema::create('{$tableName}', function (Blueprint \$table) {";
        };

        $content = preg_replace_callback($pattern, $replacement, $content);

        // Добавить закрывающую скобку после закрытия функции
        $content = preg_replace('/(Schema::create\([^;]*?\}\);)\n(\s*)(\})/m', "$1\n$2}\n$2}", $content);

        return $content;
    }

    /**
     * Добавить комментарии к таблицам
     */
    private function addTableComments(string $content): string
    {
        // Найти Schema::create и добавить comment сразу после открытия
        
        $pattern = '/Schema::create\([\'"](\w+)[\'"]\s*,\s*function\s*\(\s*Blueprint\s+\$table\s*\)\s*\{\n/';
        
        $replacement = function($matches) {
            $tableName = $matches[1];
            $comment = $this->generateTableComment($tableName);
            return $matches[0] . "                \$table->comment('{$comment}');\n\n";
        };

        return preg_replace_callback($pattern, $replacement, $content);
    }

    /**
     * Сгенерировать комментарий для таблицы
     */
    private function generateTableComment(string $tableName): string
    {
        $comments = [
            'users' => 'Пользователи системы с аутентификацией',
            'password_reset_tokens' => 'Токены для сброса пароля',
            'sessions' => 'Сессии пользователей',
            'permissions' => '权限 и роли для RBAC',
            'roles' => 'Роли пользователей',
            'transactions' => 'Транзакции кошельков (Bavix)',
            'wallets' => 'Кошельки пользователей/организаций',
            'transfers' => 'Переводы между кошельками',
            'jobs' => 'Очередь асинхронных заданий',
            'job_batches' => 'Батчи задач для Horizon',
            'failed_jobs' => 'Логирование неудачных задач',
        ];

        return $comments[$tableName] ?? "Таблица {$tableName}";
    }

    /**
     * Добавить correlation_id где его нет
     */
    private function addCorrelationId(string $content): string
    {
        // Найти блоки создания таблиц и добавить correlation_id перед timestamps() или перед закрытием
        
        // Простая версия: добавить перед }) если нет correlation_id
        if (preg_match('/correlation_id/', $content)) {
            return $content; // Уже есть
        }

        // Найти место для вставки (перед последним полем или перед })
        $content = preg_replace(
            '/(\$table->(timestamps|softDeletes)\(\);)/',
            "\n            // Traceability\n            \$table->string('correlation_id')->nullable()->index()->comment('Correlation ID для трассировки');\n\n            \$1",
            $content
        );

        return $content;
    }

    /**
     * Конвертировать json в jsonb
     */
    private function convertJsonToJsonb(string $content): string
    {
        // ->json(...) -> ->jsonb(...)
        return preg_replace('/->json\(/', '->jsonb(', $content);
    }

    /**
     * Добавить tags поле
     */
    private function addTags(string $content): string
    {
        if (preg_match("/'tags'|->tags|\['tags'\]/", $content)) {
            return $content; // Уже есть
        }

        // Добавить перед последним полем или перед закрытием
        $content = preg_replace(
            '/(\$table->(timestamps|softDeletes)\(\);)/',
            "\n            // Analytics\n            \$table->jsonb('tags')->nullable()->comment('Теги для категоризации и аналитики');\n\n            \$1",
            $content
        );

        return $content;
    }

    /**
     * Добавить Production 2026 документацию
     */
    private function addProductionDocblock(string $content): string
    {
        // Обновить docblock метода up()
        $pattern = '/\/\*\*\s*\n\s*\*\s*Run the migrations\./';
        
        $replacement = "/**\n     * Run the migrations.\n     *\n     * Production 2026: Идempotent, с трассировкой (correlation_id),\n     * аналитикой (tags), составными индексами и полной документацией.";
        
        return preg_replace($pattern, $replacement, $content, 1);
    }

    /**
     * Вывести отчёт
     */
    public function printReport(): void
    {
        echo "\n=== АНАЛИЗ МИГРАЦИЙ ===\n";
        echo "Всего файлов: {$this->report['total']}\n";
        echo "Требует рефакторинга: {$this->report['processed']}\n";
        echo "Хорошего качества: {$this->report['skipped']}\n";
        
        if (!empty($this->report['errors'])) {
            echo "\nОшибки:\n";
            foreach ($this->report['errors'] as $error) {
                echo "  - {$error}\n";
            }
        }

        echo "\n=== ДЕТАЛЬ ПО ФАЙЛАМ ===\n";
        foreach ($this->report['files'] as $filename => $analysis) {
            if (empty($analysis['needsRefactoring'])) {
                continue;
            }

            echo "\n{$filename}:\n";
            echo "  Таблицы: " . implode(', ', $analysis['tables']) . "\n";
            echo "  Требует:\n";
            foreach ($analysis['needsRefactoring'] as $need) {
                echo "    - {$need}\n";
            }
        }
    }

    /**
     * Экспортировать отчёт в JSON
     */
    public function exportReport(string $filename): void
    {
        file_put_contents($filename, json_encode($this->report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

// === ГЛАВНЫЙ СКРИПТ ===

if (php_sapi_name() !== 'cli') {}

$refactor = new MigrationRefactor('database/migrations/tenant');

echo "Анализирую миграции...\n";
$refactor->analyze();
$refactor->printReport();
$refactor->exportReport('migration_analysis.json');

echo "\n✅ Анализ завершён. Результаты сохранены в migration_analysis.json\n";
