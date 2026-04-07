<?php declare(strict_types=1);

namespace App\Services\Performance;

use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class DatabaseIndexingService
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}


    /**
         * Рекомендуемые индексы по таблицам (для production)
         */
        private const RECOMMENDED_INDEXES = [
            'users' => [
                ['columns' => ['tenant_id', 'status'], 'unique' => false],
                ['columns' => ['email'], 'unique' => true],
                ['columns' => ['created_at'], 'unique' => false],
            ],
            'orders' => [
                ['columns' => ['user_id', 'status'], 'unique' => false],
                ['columns' => ['tenant_id', 'created_at'], 'unique' => false],
                ['columns' => ['correlation_id'], 'unique' => true],
            ],
            'balance_transactions' => [
                ['columns' => ['user_id', 'created_at'], 'unique' => false],
                ['columns' => ['tenant_id', 'type'], 'unique' => false],
                ['columns' => ['status'], 'unique' => false],
            ],
            'products' => [
                ['columns' => ['tenant_id', 'status'], 'unique' => false],
                ['columns' => ['category_id'], 'unique' => false],
                ['columns' => ['created_at'], 'unique' => false],
            ],
            'user_views' => [
                ['columns' => ['user_id', 'product_id', 'created_at'], 'unique' => false],
                ['columns' => ['product_id'], 'unique' => false],
            ],
            'promo_campaigns' => [
                ['columns' => ['tenant_id', 'status'], 'unique' => false],
                ['columns' => ['code'], 'unique' => true],
            ],
        ];

        /**
         * Создаёт все рекомендуемые индексы
         *
         * @return array {created: int, failed: int, errors: array}
         */
        public static function createRecommendedIndexes(): array
        {
            $created = 0;
            $failed = 0;
            $errors = [];

            foreach (self::RECOMMENDED_INDEXES as $table => $indexes) {
                foreach ($indexes as $indexConfig) {
                    try {
                        self::createIndex(
                            $table,
                            $indexConfig['columns'],
                            $indexConfig['unique'] ?? false
                        );
                        $created++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $errors[] = [
                            'table' => $table,
                            'columns' => $indexConfig['columns'],
                            'error' => $e->getMessage()
                        ];

                        $this->logger->channel('performance')->error('Index creation failed', [
                            'table' => $table,
                            'columns' => $indexConfig['columns'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            $this->logger->channel('performance')->info('Indexes creation summary', [
                'created' => $created,
                'failed' => $failed,
                'errors' => $errors
            ]);

            return compact('created', 'failed', 'errors');
        }

        /**
         * Создаёт индекс на таблице
         *
         * @param string $table
         * @param array $columns
         * @param bool $unique
         * @return bool
         */
        public static function createIndex(string $table, array $columns, bool $unique = false): bool
        {
            $columnsStr = implode('_', $columns);
            $indexName = "{$table}_{$columnsStr}_idx";
            $indexType = $unique ? 'UNIQUE INDEX' : 'INDEX';
            $columnsStr = implode(', ', $columns);

            $sql = "ALTER TABLE {$table} ADD {$indexType} {$indexName} ({$columnsStr})";

            try {
                $this->db->statement($sql);

                $this->logger->channel('performance')->info('Index created', [
                    'table' => $table,
                    'index' => $indexName,
                    'columns' => $columns
                ]);

                return true;

            } catch (\Throwable $e) {
                // Index уже существует - не ошибка
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    return true;
                }
                throw $e;
            }
        }

        /**
         * Получает список существующих индексов таблицы
         *
         * @param string $table
         * @return array
         */
        public static function getTableIndexes(string $table): array
        {
            $results = $this->db->select("SHOW INDEXES FROM {$table}");

            $indexes = [];
            foreach ($results as $index) {
                $key = $index->Key_name;
                if ($key === 'PRIMARY') {
                    continue; // Пропускаем первичный ключ
                }

                if (!isset($indexes[$key])) {
                    $indexes[$key] = [
                        'name' => $key,
                        'columns' => [],
                        'unique' => !$index->Non_unique,
                        'seq' => []
                    ];
                }

                $indexes[$key]['columns'][] = $index->Column_name;
                $indexes[$key]['seq'][$index->Column_name] = $index->Seq_in_index;
            }

            return $indexes;
        }

        /**
         * Анализирует размер индексов (для мониторинга)
         *
         * @return array
         */
        public static function getIndexSizes(): array
        {
            $results = $this->db->select("
                SELECT
                    TABLE_NAME,
                    INDEX_NAME,
                    STAT_VALUE * @@innodb_page_size / 1024 / 1024 as size_mb
                FROM mysql.innodb_index_stats
                WHERE STAT_NAME = 'size'
                ORDER BY STAT_VALUE DESC
                LIMIT 50
            ");

            $indexes = [];
            foreach ($results as $row) {
                $indexes[] = [
                    'table' => $row->TABLE_NAME,
                    'index' => $row->INDEX_NAME,
                    'size_mb' => round($row->size_mb, 2)
                ];
            }

            return $indexes;
        }

        /**
         * Удаляет неиспользуемые индексы
         *
         * @return array {dropped: int, errors: array}
         */
        public static function dropUnusedIndexes(): array
        {
            $dropped = 0;
            $errors = [];

            $results = $this->db->select("
                SELECT
                    OBJECT_SCHEMA,
                    OBJECT_NAME,
                    INDEX_NAME
                FROM performance_schema.table_io_waits_summary_by_index_usage
                WHERE INDEX_NAME != 'PRIMARY'
                AND COUNT_STAR = 0
                AND OBJECT_SCHEMA != 'mysql'
                LIMIT 20
            ");

            foreach ($results as $row) {
                try {
                    $this->db->statement("ALTER TABLE {$row->OBJECT_SCHEMA}.{$row->OBJECT_NAME} DROP INDEX {$row->INDEX_NAME}");
                    $dropped++;

                    $this->logger->channel('performance')->info('Unused index dropped', [
                        'table' => "{$row->OBJECT_SCHEMA}.{$row->OBJECT_NAME}",
                        'index' => $row->INDEX_NAME
                    ]);

                } catch (\Throwable $e) {
                    $errors[] = [
                        'table' => $row->OBJECT_NAME,
                        'index' => $row->INDEX_NAME,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return compact('dropped', 'errors');
        }

        /**
         * Получает SQL-скрипт для создания всех индексов (для документации)
         *
         * @return string
         */
        public static function getIndexCreationScript(): string
        {
            $script = "-- Database Indexes Creation Script\n";
            $script .= "-- Generated: " . now()->toDateTimeString() . "\n\n";

            foreach (self::RECOMMENDED_INDEXES as $table => $indexes) {
                $script .= "-- Indexes for {$table}\n";

                foreach ($indexes as $indexConfig) {
                    $columnsStr = implode(', ', $indexConfig['columns']);
                    $columnsKey = implode('_', $indexConfig['columns']);
                    $indexName = "{$table}_{$columnsKey}_idx";
                    $indexType = $indexConfig['unique'] ? 'UNIQUE INDEX' : 'INDEX';

                    $script .= "ALTER TABLE {$table} ADD {$indexType} {$indexName} ({$columnsStr});\n";
                }

                $script .= "\n";
            }

            return $script;
        }
}
