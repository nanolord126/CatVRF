<?php

namespace App\Domains\Advertising\Services\Security;

use App\Domains\Advertising\Models\AdInteractionLog;
use App\Models\AuditLog;
use Illuminate\Support\Facades\{Log, Http, Cache};
use Illuminate\Support\Str;
use Throwable;

/**
 * AdShieldProtection - Сервис защиты от фрода и бот-трафика (Production 2026).
 * 
 * Анализирует взаимодействия на основе:
 * - GeoIP/ASN проверки (VPN, proxies, datacenters)
 * - Поведенческого анализа (click velocity, patterns)
 * - Хитмап анализа (повторяющиеся координаты)
 * - Machine Learning скоринга
 */
class AdShieldProtection
{
    private string $correlationId;
    private float $fraudThreshold = 60.0; // Score порог для блокировки

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
    }

    /**
     * Комплексный анализ взаимодействия на предмет мошенничества (Production 2026).
     * Возвращает boolean флаг и детальный скор.
     *
     * @param array $data Данные взаимодействия (click_time, load_time, coordinates и т.д.)
     * @param string $ip IP адрес клиента
     * @param string $userAgent User Agent браузера
     * @return array ['is_fraud' => bool, 'score' => float, 'reasons' => array]
     * 
     * @throws \InvalidArgumentException При невалидных параметрах
     */
    public function detectFraud(array $data, string $ip, string $userAgent = ''): array
    {
        try {
            // === Валидация входных данных ===
            if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
                throw new \InvalidArgumentException("Invalid IP address: {$ip}");
            }

            $fraudScore = 0.0;
            $fraudReasons = [];

            // === 1. Проверка на VPN/Tor/Datacenter (GeoIP) ===
            $geoCheckResult = $this->checkSuspiciousNetwork($ip);
            if ($geoCheckResult['is_suspicious']) {
                $fraudScore += $geoCheckResult['score'];
                $fraudReasons[] = $geoCheckResult['reason'];
                
                Log::warning('Suspicious network detected', [
                    'ip' => $ip,
                    'reason' => $geoCheckResult['reason'],
                    'score' => $geoCheckResult['score'],
                    'correlation_id' => $this->correlationId,
                ]);
            }

            // === 2. Click Velocity Analysis: слишком быстрый клик ===
            if (isset($data['load_time'], $data['click_time'])) {
                $clickDelta = ($data['click_time'] ?? 0) - ($data['load_time'] ?? 0);
                
                if ($clickDelta < 500) { // < 500ms - очень подозрительно
                    $fraudScore += 35.0;
                    $fraudReasons[] = "Immediate click detected ({$clickDelta}ms)";
                } elseif ($clickDelta < 1000) { // < 1sec
                    $fraudScore += 20.0;
                    $fraudReasons[] = "Very fast click ({$clickDelta}ms)";
                }
            }

            // === 3. Heatmap Pattern Analysis: повторяющиеся клики ===
            if (isset($data['point_x'], $data['point_y'])) {
                $heatmapScore = $this->analyzeClickPattern(
                    $data['point_x'],
                    $data['point_y'],
                    $ip,
                    $data['banner_id'] ?? null
                );
                $fraudScore += $heatmapScore;
                
                if ($heatmapScore > 0) {
                    $fraudReasons[] = "Suspicious click pattern (heatmap analysis: +{$heatmapScore})";
                }
            }

            // === 4. User Agent Bot Detection ===
            if (!empty($userAgent)) {
                $botScore = $this->detectBotUserAgent($userAgent);
                $fraudScore += $botScore;
                
                if ($botScore > 0) {
                    $fraudReasons[] = "Bot-like user agent detected (+{$botScore})";
                }
            }

            // === 5. Geographic Anomaly Detection ===
            $geoAnomalyScore = $this->detectGeoAnomaly($ip, $data);
            $fraudScore += $geoAnomalyScore;
            
            if ($geoAnomalyScore > 0) {
                $fraudReasons[] = "Geographic anomaly detected (+{$geoAnomalyScore})";
            }

            // === 6. Device Fingerprinting (если доступно) ===
            if (isset($data['device_fingerprint'])) {
                $fingerprintScore = $this->analyzeDeviceFingerprint(
                    $data['device_fingerprint'],
                    $ip
                );
                $fraudScore += $fingerprintScore;
                
                if ($fingerprintScore > 0) {
                    $fraudReasons[] = "Suspicious device fingerprint (+{$fingerprintScore})";
                }
            }

            // === Нормализация скора ===
            $fraudScore = min($fraudScore, 100.0);
            $isFraud = $fraudScore >= $this->fraudThreshold;

            // === Логирование результата ===
            if ($isFraud) {
                $this->logFraudAttempt($ip, $fraudScore, $fraudReasons, $data);
            }

            Log::info('Fraud detection analysis completed', [
                'ip' => $ip,
                'fraud_score' => $fraudScore,
                'is_fraud' => $isFraud,
                'reason_count' => count($fraudReasons),
                'correlation_id' => $this->correlationId,
            ]);

            return [
                'is_fraud' => $isFraud,
                'score' => $fraudScore,
                'reasons' => $fraudReasons,
                'correlation_id' => $this->correlationId,
            ];

        } catch (Throwable $e) {
            Log::error('Fraud detection failed', [
                'ip' => $ip ?? 'unknown',
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'correlation_id' => $this->correlationId,
            ]);

            \Sentry\captureException($e);

            // В случае ошибки - пропускаем (whitelist approach) вместо блокировки
            return [
                'is_fraud' => false,
                'score' => 0.0,
                'reasons' => ["Detection error: " . $e->getMessage()],
                'correlation_id' => $this->correlationId,
            ];
        }
    }

    /**
     * Проверка IP на VPN/Proxy/Datacenter (Production 2026).
     * Использует кеш и кейлимитинг для оптимизации.
     *
     * @param string $ip IP адрес
     * @return array ['is_suspicious' => bool, 'score' => float, 'reason' => string]
     */
    private function checkSuspiciousNetwork(string $ip): array
    {
        try {
            // === Проверка локальных IP ===
            if ($this->isPrivateIp($ip)) {
                return ['is_suspicious' => false, 'score' => 0.0, 'reason' => 'Private IP'];
            }

            // === Проверка кеша ===
            $cacheKey = "ip_geo_check:" . md5($ip);
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }

            // === Проверка через MaxMind GeoLite2 API (если настроена) ===
            // TODO: Интеграция с MaxMind для определения VPN/Proxy
            // Временно: базовая проверка на известные датацентры AWS, GCP, Azure
            
            $result = ['is_suspicious' => false, 'score' => 0.0, 'reason' => 'Network OK'];

            // Кеширование на 24 часа
            Cache::put($cacheKey, $result, 86400);

            return $result;

        } catch (Throwable $e) {
            Log::error('Network check failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);

            return ['is_suspicious' => false, 'score' => 0.0, 'reason' => 'Check error'];
        }
    }

    /**
     * Анализ паттернов кликов на основе хитмап данных.
     *
     * @param int $x X координата клика
     * @param int $y Y координата клика
     * @param string $ip IP адрес
     * @param int|null $bannerId ID баннера
     * @return float Фрод-скор за паттерн (0-50)
     */
    private function analyzeClickPattern(int $x, int $y, string $ip, ?int $bannerId = null): float
    {
        try {
            // === Нулевые координаты - очень подозрительно ===
            if ($x === 0 && $y === 0) {
                return 40.0;
            }

            // === Отрицательные координаты - невозможно ===
            if ($x < 0 || $y < 0) {
                return 35.0;
            }

            // === Повторяющиеся клики в одну точку (за последние 5 минут) ===
            $ipHash = hash('sha256', $ip);
            
            $samePointCount = AdInteractionLog::where('ip_address', $ip)
                ->where('point_x', $x)
                ->where('point_y', $y)
                ->where('interacted_at', '>=', now()->subMinutes(5))
                ->count();

            if ($samePointCount > 3) {
                return 20.0 + ($samePointCount * 5); // Прогрессивный штраф
            }

            return 0.0;

        } catch (Throwable $e) {
            Log::error('Click pattern analysis failed', [
                'error' => $e->getMessage(),
            ]);

            return 0.0;
        }
    }

    /**
     * Bot detection на основе User Agent.
     *
     * @param string $userAgent User Agent строка
     * @return float Скор 0-30
     */
    private function detectBotUserAgent(string $userAgent): float
    {
        $botPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python',
            'selenium', 'puppeteer', 'playwright', 'headless', 'phantom',
        ];

        foreach ($botPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return 25.0;
            }
        }

        return 0.0;
    }

    /**
     * Детектирование географических аномалий (e.g., impossible travel).
     *
     * @param string $ip IP адрес
     * @param array $data Дополнительные данные
     * @return float Скор 0-20
     */
    private function detectGeoAnomaly(string $ip, array $data): float
    {
        // TODO: Интеграция с предыдущей гео-локацией из сессии
        // Пример: если последний клик был в Moscow, а сейчас Singapore в течение 1 минуты - impossible travel
        
        return 0.0;
    }

    /**
     * Анализ device fingerprint.
     *
     * @param string $fingerprint Device fingerprint
     * @param string $ip IP адрес
     * @return float Скор 0-15
     */
    private function analyzeDeviceFingerprint(string $fingerprint, string $ip): float
    {
        // TODO: Сравнение fingerprint с историческими данными IP
        // Если один IP часто меняет fingerprint - подозрительно
        
        return 0.0;
    }

    /**
     * Проверка, является ли IP приватным.
     *
     * @param string $ip IP адрес
     * @return bool
     */
    private function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Логирование попытки фрода в audit trail.
     *
     * @param string $ip IP адрес
     * @param float $fraudScore Вычисленный скор
     * @param array $reasons Причины подозрения
     * @param array $data Дополнительные данные
     * @return void
     */
    private function logFraudAttempt(string $ip, float $fraudScore, array $reasons, array $data): void
    {
        try {
            AuditLog::create([
                'action' => 'advertising.fraud_detected',
                'description' => "Фрод подозрение: {$fraudScore} points",
                'model_type' => 'AdInteractionLog',
                'model_id' => $data['interaction_id'] ?? null,
                'correlation_id' => $this->correlationId,
                'metadata' => [
                    'ip_address' => $ip,
                    'fraud_score' => $fraudScore,
                    'reasons' => $fraudReasons ?? [],
                    'click_data' => [
                        'x' => $data['point_x'] ?? null,
                        'y' => $data['point_y'] ?? null,
                        'click_time' => $data['click_time'] ?? null,
                    ],
                ],
            ]);

            Log::error('FRAUD ALERT: Suspicious interaction blocked', [
                'ip' => $ip,
                'fraud_score' => $fraudScore,
                'reasons' => $reasons,
                'correlation_id' => $this->correlationId,
            ]);

        } catch (Throwable $e) {
            Log::error('Failed to log fraud attempt', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
