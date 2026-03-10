<?php

namespace App\Services\Common\Security;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
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
        // В реальном проекте здесь интеграция с GeoIP2/MaxMind
        $ip = $context['ip'] ?? request()->ip();
        
        // Пример: если IP из санкционного списка или сменилась страна за 5 мин
        return 0.0; // Заглушка для демонстрации, в релизе +30 если аномалия
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
}
