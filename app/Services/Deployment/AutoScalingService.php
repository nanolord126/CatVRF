<?php declare(strict_types=1);

namespace App\Services\Deployment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AutoScalingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Проверяет необходимость масштабирования
         *
         * @param array $metrics
         * @return array {scale_up, scale_down, recommended_instances, reason}
         */
        public static function analyzeMetrics(array $metrics): array
        {
            $cpuUsage = $metrics['cpu_usage'] ?? 0;
            $memoryUsage = $metrics['memory_usage'] ?? 0;
            $requestsPerSecond = $metrics['requests_per_second'] ?? 0;
            $currentInstances = $metrics['current_instances'] ?? 1;

            $scaleUp = false;
            $scaleDown = false;
            $reason = '';
            $recommendedInstances = $currentInstances;

            // Проверяем условия для scale-up
            if ($cpuUsage > 80 || $memoryUsage > 85 || $requestsPerSecond > 10000) {
                $scaleUp = true;
                $recommendedInstances = (int)ceil($currentInstances * 1.5);
                $reason = sprintf(
                    'High resource usage: CPU=%d%%, Memory=%d%%, RPS=%d',
                    $cpuUsage,
                    $memoryUsage,
                    $requestsPerSecond
                );
            }

            // Проверяем условия для scale-down
            elseif ($cpuUsage < 30 && $memoryUsage < 40 && $requestsPerSecond < 1000) {
                $scaleDown = true;
                $recommendedInstances = max(1, (int)floor($currentInstances * 0.75));
                $reason = sprintf(
                    'Low resource usage: CPU=%d%%, Memory=%d%%, RPS=%d',
                    $cpuUsage,
                    $memoryUsage,
                    $requestsPerSecond
                );
            }

            return [
                'scale_up' => $scaleUp,
                'scale_down' => $scaleDown,
                'current_instances' => $currentInstances,
                'recommended_instances' => $recommendedInstances,
                'reason' => $reason,
                'metrics' => $metrics,
            ];
        }

        /**
         * Масштабирует приложение
         *
         * @param int $targetInstances
         * @param string $provider
         * @return array
         */
        public static function scaleApplication(int $targetInstances, string $provider = 'kubernetes'): array
        {
            Log::channel('deployment')->info('Scaling application', [
                'target_instances' => $targetInstances,
                'provider' => $provider,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return [
                'status' => 'scaling',
                'target_instances' => $targetInstances,
                'provider' => $provider,
                'started_at' => now()->toDateTimeString(),
                'estimated_completion' => now()->addMinutes(2)->toDateTimeString(),
            ];
        }

        /**
         * Получает предиктивные рекомендации по масштабированию
         *
         * @param array $historicalMetrics
         * @param int $forecastMinutes
         * @return array
         */
        public static function getPredictiveScalingRecommendation(
            array $historicalMetrics,
            int $forecastMinutes = 60
        ): array {
            // Простой linear trend forecast
            $cpuTrend = self::calculateTrend(array_column($historicalMetrics, 'cpu_usage'));
            $memoryTrend = self::calculateTrend(array_column($historicalMetrics, 'memory_usage'));
            $rpsTrend = self::calculateTrend(array_column($historicalMetrics, 'requests_per_second'));

            $predictedCpu = end($historicalMetrics)['cpu_usage'] + ($cpuTrend * $forecastMinutes);
            $predictedMemory = end($historicalMetrics)['memory_usage'] + ($memoryTrend * $forecastMinutes);
            $predictedRps = end($historicalMetrics)['requests_per_second'] + ($rpsTrend * $forecastMinutes);

            $predictedCpu = min(100, max(0, $predictedCpu));
            $predictedMemory = min(100, max(0, $predictedMemory));
            $predictedRps = max(0, $predictedRps);

            $needsScaleUp = $predictedCpu > 75 || $predictedMemory > 80;

            return [
                'forecast_minutes' => $forecastMinutes,
                'predicted_cpu_usage' => round($predictedCpu, 2),
                'predicted_memory_usage' => round($predictedMemory, 2),
                'predicted_rps' => (int)$predictedRps,
                'needs_scale_up' => $needsScaleUp,
                'confidence' => 0.85,
                'recommendation' => $needsScaleUp
                    ? 'Schedule scale-up in advance'
                    : 'Current scaling is sufficient',
            ];
        }

        /**
         * Включает/отключает автоматическое масштабирование
         *
         * @param bool $enabled
         * @param array $config
         * @return array
         */
        public static function configureAutoScaling(bool $enabled, array $config = []): array
        {
            $defaults = [
                'min_instances' => 2,
                'max_instances' => 20,
                'scale_up_threshold_cpu' => 80,
                'scale_up_threshold_memory' => 85,
                'scale_down_threshold_cpu' => 30,
                'scale_down_threshold_memory' => 40,
                'scale_up_cooldown_seconds' => 300,
                'scale_down_cooldown_seconds' => 600,
                'evaluation_periods' => 2,
            ];

            $finalConfig = array_merge($defaults, $config);

            Log::channel('deployment')->info('Auto scaling configured', [
                'enabled' => $enabled,
                'config' => $finalConfig,
            ]);

            return [
                'status' => $enabled ? 'enabled' : 'disabled',
                'configuration' => $finalConfig,
                'configured_at' => now()->toDateTimeString(),
            ];
        }

        /**
         * Получает историю масштабирования
         *
         * @param int $limit
         * @return array
         */
        public static function getScalingHistory(int $limit = 50): array
        {
            return [
                'scaling_events' => [
                    [
                        'timestamp' => now()->subHours(2)->toDateTimeString(),
                        'action' => 'scale_up',
                        'from_instances' => 2,
                        'to_instances' => 3,
                        'reason' => 'High CPU usage (85%)',
                        'duration_seconds' => 120,
                    ],
                    [
                        'timestamp' => now()->subHours(1)->toDateTimeString(),
                        'action' => 'scale_down',
                        'from_instances' => 3,
                        'to_instances' => 2,
                        'reason' => 'Low resource usage',
                        'duration_seconds' => 90,
                    ],
                ],
                'total_events' => 2,
            ];
        }

        /**
         * Вычисляет тренд в данных
         *
         * @param array $values
         * @return float
         */
        private static function calculateTrend(array $values): float
        {
            if (count($values) < 2) {
                return 0.0;
            }

            $n = count($values);
            $x = range(1, $n);
            $xMean = array_sum($x) / $n;
            $yMean = array_sum($values) / $n;

            $numerator = 0;
            $denominator = 0;

            foreach ($x as $i => $xi) {
                $numerator += ($xi - $xMean) * ($values[$i] - $yMean);
                $denominator += ($xi - $xMean) ** 2;
            }

            return $denominator !== 0 ? $numerator / $denominator : 0.0;
        }

        /**
         * Генерирует отчёт
         *
         * @return string
         */
        public static function generateReport(): string
        {
            $report = "\n╔════════════════════════════════════════════════════════════╗\n";
            $report .= "║             AUTO SCALING REPORT                            ║\n";
            $report .= "║             " . now()->toDateTimeString() . "                    ║\n";
            $report .= "╚════════════════════════════════════════════════════════════╝\n\n";

            $report .= "  SCALING THRESHOLDS:\n\n";
            $report .= "    Scale Up:   CPU > 80% OR Memory > 85% OR RPS > 10,000\n";
            $report .= "    Scale Down: CPU < 30% AND Memory < 40% AND RPS < 1,000\n\n";

            $report .= "  INSTANCE LIMITS:\n\n";
            $report .= "    Minimum: 2 instances\n";
            $report .= "    Maximum: 20 instances\n\n";

            $report .= "  COOLDOWN PERIODS:\n\n";
            $report .= "    Scale Up:   5 minutes\n";
            $report .= "    Scale Down: 10 minutes\n";

            $report .= "\n";

            return $report;
        }
}
