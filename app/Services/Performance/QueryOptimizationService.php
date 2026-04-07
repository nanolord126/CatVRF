<?php declare(strict_types=1);

namespace App\Services\Performance;




use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class QueryOptimizationService
{
    public function __construct(
        private readonly Request $request,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}


    private const SLOW_QUERY_THRESHOLD = 100; // мс
        private const QUERY_LOG_LIMIT = 50;

        /**
         * Включает логирование медленных запросов
         *
         * @return void
         */
        public static function enableSlowQueryLogging(): void
        {
            $this->db->listen(function ($query) {
                $time = $query->time;

                if ($time > self::SLOW_QUERY_THRESHOLD) {
                    $this->logger->channel('performance')->warning('Slow query detected', [
                        'query' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $time . 'ms',
                        'timestamp' => now()->toIso8601String(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                }
            });
        }

        /**
         * Добавляет автоматический eager loading для связей
         * Предотвращает N+1 проблему
         *
         * @param EloquentBuilder $query
         * @param array $relations
         * @return EloquentBuilder
         */
        public static function withEagerLoad(EloquentBuilder $query, array $relations): EloquentBuilder
        {
            if (empty($relations)) {
                return $query;
            }

            $this->logger->channel('performance')->debug('Eager loading applied', [
                'relations' => $relations,
                'model' => $query->getModel()::class,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

            return $query->with($relations);
        }

        /**
         * Оптимизирует query для большого объёма данных (pagination)
         * Использует cursor-based pagination для эффективности
         *
         * @param Builder|EloquentBuilder $query
         * @param int $perPage
         * @return \Illuminate\Pagination\Paginator
         */
        public static function cursorPaginate($query, int $perPage = 15)
        {
            return $query->cursorPaginate($perPage);
        }

        /**
         * Добавляет индексы на часто используемые столбцы (для миграций)
         * Возвращает SQL для добавления индексов
         *
         * @param string $table
         * @param array $columns
         * @param string $indexType
         * @return array
         */
        public static function getIndexSQL(
            string $table,
            array $columns,
            string $indexType = 'INDEX'
        ): array {
            $results = [];

            foreach ($columns as $column) {
                $indexName = "{$table}_{$column}_idx";
                $sql = "ALTER TABLE {$table} ADD {$indexType} {$indexName} ({$column})";
                $results[] = $sql;
            }

            return $results;
        }

        /**
         * Анализирует запрос через EXPLAIN для оптимизации
         *
         * @param string $sql
         * @param array $bindings
         * @return array
         */
        public static function analyzeQuery(string $sql, array $bindings = []): array
        {
            try {
                $results = $this->db->select('EXPLAIN ' . $sql, $bindings);

                $this->logger->channel('performance')->info('Query analysis', [
                    'query' => $sql,
                    'rows_examined' => $results[0]->rows ?? 0,
                ]);

                return $results;

            } catch (\Throwable $e) {
                $this->logger->channel('performance')->error('Query analysis failed', [
                    'query' => $sql,
                    'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                return [];
            }
        }

        /**
         * Рекомендирует оптимизации на основе запроса
         *
         * @param array $explainResult
         * @return array
         */
        public static function getOptimizationRecommendations(array $explainResult): array
        {
            $recommendations = [];

            if (empty($explainResult)) {
                return $recommendations;
            }

            $row = $explainResult[0] ?? null;
            if (!$row) {
                return $recommendations;
            }

            // Проверяем, используется ли индекс
            if (empty($row->key)) {
                $recommendations[] = 'No index used - consider adding an index on frequently filtered columns';
            }

            // Проверяем количество строк для сканирования
            if (isset($row->rows) && $row->rows > 1000) {
                $recommendations[] = 'Large number of rows scanned - optimize with WHERE conditions or indexes';
            }

            // Проверяем тип доступа
            if ($row->type === 'ALL') {
                $recommendations[] = 'Full table scan detected - add appropriate indexes';
            }

            return $recommendations;
        }

        /**
         * Получает все медленные запросы из логов
         *
         * @return array
         */
        public static function getSlowQueries(): array
        {
            // Реальная реализация зависит от хранилища логов (файлы, БД, etc.)
            // Здесь показан пример с файлами логов

            $logFile = storage_path('logs/performance.log');

            if (!file_exists($logFile)) {
                return [];
            }

            $lines = array_slice(file($logFile), -self::QUERY_LOG_LIMIT);
            $slowQueries = [];

            foreach ($lines as $line) {
                if (strpos($line, 'Slow query detected') !== false) {
                    $slowQueries[] = $line;
                }
            }

            return $slowQueries;
        }

        /**
         * Профилирует блок кода и возвращает метрики
         *
         * @param callable $callback
         * @param string $label
         * @return mixed
         */
        public static function profile(callable $callback, string $label = 'Code block'): mixed
        {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);

            $result = $callback();

            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $executionTime = ($endTime - $startTime) * 1000; // мс
            $memoryUsed = ($endMemory - $startMemory) / 1024; // КБ

            $this->logger->channel('performance')->info('Code profiling', [
                'label' => $label,
                'execution_time_ms' => round($executionTime, 2),
                'memory_used_kb' => round($memoryUsed, 2),
                'timestamp' => now()->toIso8601String(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

            return $result;
        }

        /**
         * Проверяет наличие N+1 проблемы (множественные запросы вместо одного)
         *
         * @return int
         */
        public static function detectNPlusOne(): int
        {
            $queries = $this->db->getQueryLog();

            // Анализируем повторяющиеся запросы
            $queryPatterns = [];
            foreach ($queries as $query) {
                $pattern = preg_replace('/\d+/', '?', $query['query']);
                $queryPatterns[$pattern] = ($queryPatterns[$pattern] ?? 0) + 1;
            }

            // Считаем, сколько повторяющихся паттернов (признак N+1)
            $suspiciousPatterns = array_filter($queryPatterns, fn ($count) => $count > 5);

            if (!empty($suspiciousPatterns)) {
                $this->logger->channel('performance')->warning('Possible N+1 query detected', [
                    'patterns' => $suspiciousPatterns,
                    'total_queries' => count($queries),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
            }

            return count($suspiciousPatterns);
        }
}
