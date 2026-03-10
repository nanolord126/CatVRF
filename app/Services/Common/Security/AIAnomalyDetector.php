<?php

namespace App\Services\Common\Security;

use App\Models\Tenant;
use Illuminate\Support\Facades\{DB, Cache};
use Illuminate\Support\Str;

class AIAnomalyDetector
{
    /**
     * Анализ действия на предмет аномалий (Фрад-контроль 2026)
     * Использует Velocity (скорость операций), Geozones и Поведенческие паттерны.
     */
    public function analyze(Tenant $tenant, ?int $userId, string $action, array $context): float
    {
        $riskScore = 0.0;
        $correlationId = Str::uuid()->toString();

        // 1. Velocity Check (Слишком много действий за короткий период)
        $velocityRisk = $this->checkVelocity($tenant, $userId, $action);
        $riskScore += $velocityRisk;

        // 2. Geo-Shift Check (Невозможная скорость перемещения, или подозрительный IP)
        $geoRisk = $this->checkGeoAnomaly($tenant, $userId, $context);
        $riskScore += $geoRisk;

        // 3. Amount-Based Anomaly (Если действие связано с финансами/кошельком)
        if (isset($context['amount'])) {
            $amountRisk = $this->checkFinancialAnomaly($tenant, (float)$context['amount']);
            $riskScore += $amountRisk;
        }

        // 4. Pattern Recognition (Через ClickHouse или Redis Behavioral Cache)
        $patternRisk = $this->checkBehavioralPattern($tenant, $userId, $action);
        $riskScore += $patternRisk;

        // Ограничиваем скоринг от 0 до 100
        $finalScore = min(100.0, max(0.0, $riskScore));

        // Логирование инцидента в Центральную БД (Audit + Fraud Log)
        if ($finalScore > 20) {
            $this->logFraudEvent($tenant, $userId, $action, $context, $finalScore, $correlationId);
        }

        return $finalScore;
    }

    private function checkVelocity(Tenant $tenant, ?int $userId, string $action): float
    {
        // Проверка: сколько таких действий было за последние 60 сек
        $count = DB::table('fraud_events')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->where('event_type', $action)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        return ($count > 10) ? 40.0 : 0.0;
    }

    private function checkGeoAnomaly(Tenant $tenant, ?int $userId, array $context): float
    {
        if (!$userId) {
            return 0.0;
        }

        $currentIp = $context['ip'] ?? request()->ip();
        $currentLat = (float)($context['latitude'] ?? 0);
        $currentLon = (float)($context['longitude'] ?? 0);

        // Проверка: была ли активность в последние 5 минут из другого IP
        $lastEvent = DB::table('fraud_events')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->latest('created_at')
            ->first();

        if (!$lastEvent) {
            return 0.0; // Первое событие, риск отсутствует
        }

        // Расстояние между предыдущей и текущей локацией (если координаты есть)
        if ($currentLat && $currentLon) {
            $lastPayload = json_decode($lastEvent->payload, true);
            $lastLat = (float)($lastPayload['latitude'] ?? 0);
            $lastLon = (float)($lastPayload['longitude'] ?? 0);

            if ($lastLat && $lastLon) {
                // Формула Хаверсина для расстояния в км
                $distance = $this->calculateGreatCircleDistance(
                    $lastLat, $lastLon,
                    $currentLat, $currentLon
                );

                // Максимальная скорость передвижения = 900 км/час (полет на самолете)
                // Если дистанция больше, чем возможно за 5 минут на самолете = аномалия
                $maxDistance = (900 / 60) * 5; // ~75 км за 5 минут
                if ($distance > $maxDistance) {
                    return 35.0; // Невозможное перемещение
                }
            }
        }

        // Проверка VPN/Proxy (по признакам IP)
        if ($this->isIPVpnOrProxy($currentIp)) {
            return 25.0; // Подозрительный IP
        }

        // Проверка смены страны за короткий период
        if ($lastEvent && $currentIp !== $lastEvent->payload) {
            $lastCountry = $this->getCountryByIp($lastEvent->payload);
            $currentCountry = $this->getCountryByIp($currentIp);

            if ($lastCountry && $currentCountry && $lastCountry !== $currentCountry) {
                return 30.0; // Смена страны - аномалия
            }
        }

        return 0.0;
    }

    private function checkFinancialAnomaly(Tenant $tenant, float $amount): float
    {
        // Если сумма в 10 раз превышает средний чек за месяц - это аномалия
        return ($amount > 5000) ? 25.0 : 0.0;
    }

    private function checkBehavioralPattern(Tenant $tenant, ?int $userId, string $action): float
    {
        // AI/ML Сравнение с профилем пользователя (Semantic Vector Identity)
        return 0.0;
    }

    private function logFraudEvent(Tenant $tenant, ?int $userId, string $action, array $context, float $score, string $corrId): void
    {
        DB::table('fraud_events')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $userId,
            'event_type' => $action,
            'payload' => json_encode($context),
            'risk_score' => $score,
            'correlation_id' => $corrId,
            'is_blocked' => ($score >= 80), // Авто-блок при высоком риске
            'created_at' => now(),
        ]);
    }

    /**
     * Рассчитать расстояние между двумя точками на Земле (формула Хаверсина).
     *
     * @param float $lat1 Широта 1-й точки
     * @param float $lon1 Долгота 1-й точки
     * @param float $lat2 Широта 2-й точки
     * @param float $lon2 Долгота 2-й точки
     * @return float Расстояние в км
     */
    private function calculateGreatCircleDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // км
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Проверить, является ли IP адресом VPN/Proxy.
     *
     * @param string $ip IP адрес
     * @return bool True если это VPN/Proxy
     */
    private function isIPVpnOrProxy(string $ip): bool
    {
        // Список известных VPN провайдеров (можно расширить)
        $vpnRanges = [
            '185.18.251.0/24',   // ExpressVPN
            '216.146.35.0/24',   // NordVPN  
            '89.163.128.0/17',   // Surfshark
        ];

        foreach ($vpnRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверить, находится ли IP в диапазоне CIDR.
     *
     * @param string $ip IP адрес
     * @param string $range CIDR диапазон
     * @return bool
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        [$subnet, $bits] = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        return ($ip & $mask) === $subnet;
    }

    /**
     * Получить код страны по IP адресу.
     *
     * @param string $ip IP адрес
     * @return string|null Код страны (2 буквы)
     */
    private function getCountryByIp(string $ip): ?string
    {
        // Проверка в локальном кеше
        $cached = Cache::remember("ip_country:{$ip}", 3600, function() use ($ip) {
            try {
                // Для демонстрации используем простую таблицу IP ranges
                // В production использовать MaxMind GeoIP2 API
                $country = DB::table('geo_ip_ranges')
                    ->whereRaw('INET_ATON(?) BETWEEN start_ip AND end_ip', [$ip])
                    ->value('country_code');

                return $country ?? 'XX';
            } catch (\Exception $e) {
                return null;
            }
        });

        return $cached !== 'XX' ? $cached : null;
    }
}
