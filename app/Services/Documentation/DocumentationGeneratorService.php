<?php

declare(strict_types=1);

namespace App\Services\Documentation;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

/**
 * Documentation Generator Service
 * Автоматическая генерация документации из кода
 * 
 * @package App\Services\Documentation
 * @category Documentation / Generation
 */
final class DocumentationGeneratorService
{
    /**
     * Генерирует API документацию
     * 
     * @return array
     */
    public static function generateAPIDocumentation(): array
    {
        return [
            'version' => '3.0',
            'title' => 'CatVRF Marketplace Platform API',
            'description' => 'Production-ready API documentation',
            'endpoints' => [
                [
                    'method' => 'POST',
                    'path' => '/api/v3/payments/init',
                    'description' => 'Initialize payment',
                    'parameters' => [
                        'amount' => 'int (kopeikas)',
                        'currency' => 'string (RUB)',
                        'idempotency_key' => 'uuid',
                    ],
                    'responses' => [
                        '200' => 'Payment initialized',
                        '409' => 'Duplicate payment',
                        '429' => 'Rate limit exceeded',
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/v3/marketplace/recommendations',
                    'description' => 'Get personalized recommendations',
                    'parameters' => [
                        'vertical' => 'string (optional)',
                        'limit' => 'int (default: 20)',
                    ],
                    'responses' => [
                        '200' => 'Recommendations list',
                        '401' => 'Unauthorized',
                    ],
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/v3/referrals/claim',
                    'description' => 'Claim referral bonus',
                    'parameters' => [
                        'referral_code' => 'string',
                    ],
                    'responses' => [
                        '200' => 'Bonus credited',
                        '400' => 'Invalid code',
                    ],
                ],
            ],
        ];
    }

    /**
     * Генерирует модели documentation
     * 
     * @return array
     */
    public static function generateModelsDocumentation(): array
    {
        return [
            'models' => [
                [
                    'name' => 'Wallet',
                    'description' => 'Wallet model for balance management',
                    'table' => 'wallets',
                    'fields' => [
                        'id' => 'uuid',
                        'tenant_id' => 'uuid',
                        'current_balance' => 'int (kopeikas)',
                        'hold_amount' => 'int',
                        'correlation_id' => 'uuid',
                        'created_at' => 'timestamp',
                    ],
                    'relations' => [
                        'balance_transactions' => 'hasMany',
                        'tenant' => 'belongsTo',
                    ],
                ],
                [
                    'name' => 'PaymentTransaction',
                    'description' => 'Payment transaction record',
                    'table' => 'payment_transactions',
                    'fields' => [
                        'id' => 'uuid',
                        'wallet_id' => 'uuid',
                        'amount' => 'int (kopeikas)',
                        'status' => 'enum',
                        'idempotency_key' => 'uuid',
                        'provider_payment_id' => 'string',
                        'correlation_id' => 'uuid',
                    ],
                    'relations' => [
                        'wallet' => 'belongsTo',
                        'audits' => 'hasMany',
                    ],
                ],
                [
                    'name' => 'BeautySalon',
                    'description' => 'Beauty salon in Beauty vertical',
                    'table' => 'beauty_salons',
                    'fields' => [
                        'id' => 'uuid',
                        'tenant_id' => 'uuid',
                        'name' => 'string',
                        'address' => 'string',
                        'geo_point' => 'point',
                        'rating' => 'float',
                        'is_verified' => 'boolean',
                    ],
                    'relations' => [
                        'masters' => 'hasMany',
                        'services' => 'hasMany',
                        'appointments' => 'hasMany',
                    ],
                ],
            ],
        ];
    }

    /**
     * Генерирует сервисов documentation
     * 
     * @return array
     */
    public static function generateServicesDocumentation(): array
    {
        return [
            'services' => [
                [
                    'name' => 'PaymentService',
                    'namespace' => 'App\Services\Payment',
                    'description' => 'Manages payment processing',
                    'methods' => [
                        [
                            'name' => 'initPayment',
                            'parameters' => ['amount', 'currency', 'gateway'],
                            'return' => 'PaymentResult',
                            'throws' => ['DuplicatePaymentException', 'InvalidPaymentException'],
                        ],
                        [
                            'name' => 'capturePayment',
                            'parameters' => ['payment_id'],
                            'return' => 'bool',
                            'throws' => ['PaymentNotFoundException'],
                        ],
                        [
                            'name' => 'refundPayment',
                            'parameters' => ['payment_id', 'amount'],
                            'return' => 'RefundResult',
                            'throws' => ['InvalidRefundException'],
                        ],
                    ],
                ],
                [
                    'name' => 'RecommendationService',
                    'namespace' => 'App\Services\AI',
                    'description' => 'Generates personalized recommendations',
                    'methods' => [
                        [
                            'name' => 'getForUser',
                            'parameters' => ['user_id', 'vertical', 'context'],
                            'return' => 'Collection',
                            'throws' => ['UserNotFoundException'],
                        ],
                        [
                            'name' => 'scoreItem',
                            'parameters' => ['user_id', 'item_id', 'context'],
                            'return' => 'float',
                            'throws' => ['ItemNotFoundException'],
                        ],
                    ],
                ],
                [
                    'name' => 'FraudMLService',
                    'namespace' => 'App\Services\Security',
                    'description' => 'ML-based fraud detection',
                    'methods' => [
                        [
                            'name' => 'scoreOperation',
                            'parameters' => ['operation'],
                            'return' => 'float (0-1)',
                            'throws' => ['ModelNotFoundException'],
                        ],
                        [
                            'name' => 'shouldBlock',
                            'parameters' => ['score', 'operation_type'],
                            'return' => 'bool',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Генерирует Database schema documentation
     * 
     * @return array
     */
    public static function generateDatabaseDocumentation(): array
    {
        return [
            'tables' => [
                [
                    'name' => 'wallets',
                    'description' => 'User/Business wallet balances',
                    'columns' => [
                        'id' => ['type' => 'uuid', 'nullable' => false],
                        'tenant_id' => ['type' => 'uuid', 'nullable' => false, 'indexed' => true],
                        'current_balance' => ['type' => 'integer', 'default' => 0],
                        'hold_amount' => ['type' => 'integer', 'default' => 0],
                        'correlation_id' => ['type' => 'uuid', 'nullable' => true],
                        'created_at' => ['type' => 'timestamp'],
                        'updated_at' => ['type' => 'timestamp'],
                    ],
                    'indexes' => ['tenant_id', 'created_at'],
                    'comment' => 'Хранит балансы кошельков, один кошелёк на tenant',
                ],
                [
                    'name' => 'balance_transactions',
                    'description' => 'Debit/credit transaction log',
                    'columns' => [
                        'id' => ['type' => 'uuid', 'nullable' => false],
                        'wallet_id' => ['type' => 'uuid', 'nullable' => false, 'foreign' => 'wallets.id'],
                        'type' => ['type' => 'enum', 'values' => ['deposit', 'withdrawal', 'commission', 'bonus', 'refund', 'payout']],
                        'amount' => ['type' => 'integer'],
                        'status' => ['type' => 'enum', 'values' => ['pending', 'completed', 'failed']],
                        'correlation_id' => ['type' => 'uuid', 'indexed' => true],
                    ],
                    'indexes' => ['wallet_id', 'correlation_id', 'created_at'],
                    'comment' => 'Лог всех транзакций, для аудита и восстановления баланса',
                ],
                [
                    'name' => 'fraud_attempts',
                    'description' => 'Suspicious operations log',
                    'columns' => [
                        'id' => ['type' => 'uuid'],
                        'tenant_id' => ['type' => 'uuid', 'indexed' => true],
                        'operation_type' => ['type' => 'string'],
                        'ml_score' => ['type' => 'float', 'comment' => '0-1'],
                        'decision' => ['type' => 'enum', 'values' => ['allow', 'block', 'review']],
                        'correlation_id' => ['type' => 'uuid', 'indexed' => true],
                    ],
                    'indexes' => ['tenant_id', 'created_at', 'correlation_id'],
                    'comment' => 'Лог подозрительных операций для ML-анализа',
                ],
            ],
        ];
    }

    /**
     * Генерирует Deployment guide
     * 
     * @return array
     */
    public static function generateDeploymentGuide(): array
    {
        return [
            'title' => 'CatVRF Deployment Guide',
            'version' => '3.0.0',
            'last_updated' => now()->toDateString(),
            'sections' => [
                [
                    'title' => 'Prerequisites',
                    'content' => [
                        'PHP >= 8.2',
                        'Laravel >= 11.0',
                        'PostgreSQL >= 14',
                        'Redis >= 6.0',
                        'Docker (optional)',
                    ],
                ],
                [
                    'title' => 'Installation Steps',
                    'steps' => [
                        '1. Clone repository: git clone ...',
                        '2. Install dependencies: composer install',
                        '3. Copy .env.example to .env',
                        '4. Generate app key: php artisan key:generate',
                        '5. Run migrations: php artisan migrate',
                        '6. Seed database: php artisan db:seed',
                    ],
                ],
                [
                    'title' => 'Configuration',
                    'items' => [
                        'Set DATABASE_URL in .env',
                        'Configure REDIS_URL',
                        'Set API keys for payment gateways',
                        'Configure webhook IP whitelists',
                        'Set up logging channels',
                    ],
                ],
                [
                    'title' => 'Post-Deployment',
                    'items' => [
                        'php artisan migrate (production)',
                        'php artisan queue:work (start queue)',
                        'php artisan schedule:run (cron jobs)',
                        'php artisan config:cache',
                        'php artisan optimize',
                    ],
                ],
            ],
        ];
    }

    /**
     * Экспортирует документацию в HTML
     * 
     * @param string $type
     * @return string
     */
    public static function exportAsHTML(string $type = 'api'): string
    {
        $methods = [
            'api' => 'generateAPIDocumentation',
            'models' => 'generateModelsDocumentation',
            'services' => 'generateServicesDocumentation',
            'database' => 'generateDatabaseDocumentation',
            'deployment' => 'generateDeploymentGuide',
        ];

        if (!isset($methods[$type])) {
            return '<h1>Unknown documentation type</h1>';
        }

        $docs = self::{$methods[$type]}();
        
        $html = '<html><head><meta charset="UTF-8"><style>';
        $html .= 'body{font-family:sans-serif;margin:20px;} h1{color:#333;} h2{color:#666;}';
        $html .= 'code{background:#f5f5f5;padding:2px 6px;} pre{background:#f5f5f5;padding:10px;}';
        $html .= '</style></head><body>';
        $html .= '<h1>CatVRF Documentation - ' . ucfirst($type) . '</h1>';
        $html .= '<pre>' . json_encode($docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Сохраняет документацию в файл
     * 
     * @param string $type
     * @param string $format
     * @return string
     */
    public static function saveDocumentation(string $type = 'api', string $format = 'html'): string
    {
        $path = storage_path('docs');
        if (!$this->file->exists($path)) {
            $this->file->makeDirectory($path, 0755, true);
        }

        $filename = "{$type}-documentation." . $format;
        $filepath = "{$path}/{$filename}";

        if ($format === 'html') {
            $this->file->put($filepath, self::exportAsHTML($type));
        } else {
            $this->file->put($filepath, json_encode(
                self::{ucfirst($type) . 'Documentation'}(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            ));
        }

        Log::channel('documentation')->info('Documentation saved', [
            'type' => $type,
            'format' => $format,
            'path' => $filepath,
        ]);

        return $filepath;
    }
}
