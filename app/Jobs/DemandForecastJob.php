<?php declare(strict_types=1);

namespace App\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final class DemandForecastJob implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        public int $tries = 3;
        public int $backoff = 60;

        private string $correlationId;

        /**
         * Создать новый экземпляр job.
         */
        public function __construct(string $correlationId = null,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    )
        {
            $this->correlationId = $correlationId ?? (string) Str::uuid()->toString();
        }

        /**
         * Выполнить job.
         */
        public function handle(): void
        {
            $this->logger->channel('audit')->info('DemandForecastJob started', [
                'correlation_id' => $this->correlationId,
            ]);

            try {
                $this->trainModel();

                $products = Product::where('is_active', true)->get();
                foreach ($products as $product) {
                    $this->forecastProduct($product);
                }

                $this->logger->channel('audit')->info('DemandForecastJob completed successfully', [
                    'correlation_id' => $this->correlationId,
                    'products_processed' => $products->count(),
                ]);

            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => $this->correlationId,
                ]);

                $this->logger->channel('audit')->error('DemandForecastJob failed', [
                    'correlation_id' => $this->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }

        /**
         * Симуляция обучения модели (сохранение новой версии в БД)
         */
        private function trainModel(): void
        {
            $this->logger->info('Training demand forecast model...');

            try {
                // Эмуляция задержки обучения
                sleep(2);

                // В реальном приложении здесь был бы вызов Python-скрипта или API
                // Для демо мы просто генерируем "новую версию" с хорошими метриками
                $version = now()->format('Y-m-d') . '-v' . ($this->db->table('demand_model_versions')
                    ->whereDate('trained_at', now())
                    ->count() + 1);

                // Симулировать метрики
                $metrics = [
                    'mae' => 8.5,  // Mean Absolute Error < 10%
                    'rmse' => 12.3,
                    'mape' => 9.2, // Mean Absolute Percentage Error
                ];

                $this->db->transaction(function() use ($version, $metrics) {
                    return $this->db->table('demand_model_versions')->insert([
                        'version' => $version,
                        'trained_at' => now(),
                        'mae' => $metrics['mae'],
                        'rmse' => $metrics['rmse'],
                        'mape' => $metrics['mape'],
                        'file_path' => "storage/models/demand/{$version}.joblib",
                        'comment' => 'Trained via DemandForecastJob',
                    ]);
                });

                // Активировать модель если качество хорошее
                if ($metrics['mape'] < 15) {
                    cache(['demand_model_active_version' => $version], now()->addDays(30));

                    $this->logger->info('New demand forecast model activated', [
                        'version' => $version,
                        'mape' => $metrics['mape'],
                    ]);
                }

            } catch (\Exception $e) {
                $this->logger->warning('Demand model training failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        /**
         * Сгенерировать прогноз для конкретного товара
         */
        private function forecastProduct(Product $product): void
        {
            if (!$product->tenant_id) {
                return;
            }

            // Генерация прогноза на 7 дней вперед
            for ($i = 1; $i <= 7; $i++) {
                $forecastDate = now()->addDays($i)->format('Y-m-d');

                // Простейшая симуляция: базовый спрос + случайный шум + сезонность выходных
                $baseDemand = 10;
                $noise = rand(-3, 5);
                $isWeekend = in_array(now()->addDays($i)->dayOfWeek, [0, 6]);
                $weekendMultiplier = $isWeekend ? 1.5 : 1.0;

                $predictedDemand = max(0, (int) (($baseDemand + $noise) * $weekendMultiplier));

                $result = [
                    'predicted_demand' => $predictedDemand,
                    'confidence_interval_lower' => max(0, $predictedDemand - 3),
                    'confidence_interval_upper' => $predictedDemand + 3,
                    'confidence_score' => 0.85 + (rand(0, 10) / 100),
                    'features' => json_encode([
                        'is_weekend' => $isWeekend,
                        'lag_1' => $baseDemand,
                    ]),
                ];

                try {
                    $this->db->transaction(function() use ($product, $forecastDate, $result) {
                        $this->db->table('demand_forecasts')->updateOrInsert(
                            [
                                'tenant_id' => $product->tenant_id,
                                'item_id' => $product->id,
                                'forecast_date' => $forecastDate,
                            ],
                            [
                                'predicted_demand' => $result['predicted_demand'],
                                'confidence_interval_lower' => $result['confidence_interval_lower'],
                                'confidence_interval_upper' => $result['confidence_interval_upper'],
                                'confidence_score' => $result['confidence_score'],
                                'model_version' => cache('demand_model_active_version', 'default-v1'),
                                'features_json' => $result['features'],
                                'correlation_id' => $this->correlationId,
                                'generated_at' => now(),
                            ]
                        );
                    });
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to save forecast', [
                        'product_id' => $product->id,
                        'date' => $forecastDate,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
}

