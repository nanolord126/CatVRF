<?php

declare(strict_types=1);

namespace App\Traits;

use Modules\Analytics\Services\BehavioralTracker;
use Modules\Analytics\Services\RFMService;
use Modules\Analytics\Application\Services\VerticalAnalyticsIntegrationService;
use Modules\Analytics\Application\Services\AnalyticsOrchestratorService;
use Carbon\CarbonImmutable;

/**
 * Trait для интеграции аналитики в продуктовые вертикали.
 *
 * Предоставляет методы для:
 * - Трекинга поведенческих событий
 * - RFM-анализа пользователей
 * - Получения метрик вертикали
 */
trait WithAnalyticsTracking
{
    /**
     * Зафиксировать поведенческое событие.
     */
    protected function trackEvent(
        string $eventType,
        string $vertical,
        ?string $targetId = null,
        array $payload = [],
        float $monetaryValue = 0.0,
        ?int $userId = null,
        ?BehavioralTracker $behavioralTracker = null
    ): ?\Modules\Analytics\Models\BehavioralEvent {
        $behavioralTracker ??= app(BehavioralTracker::class);
        return $behavioralTracker->capture(
            $eventType,
            $vertical,
            $targetId,
            $payload,
            $monetaryValue
        );
    }

    /**
     * Зафиксировать просмотр сущности.
     */
    protected function trackView(string $vertical, string $targetId, array $payload = [], ?BehavioralTracker $behavioralTracker = null): void
    {
        $this->trackEvent('view', $vertical, $targetId, $payload, 0.0, null, $behavioralTracker);
    }

    /**
     * Зафиксировать клик.
     */
    protected function trackClick(string $vertical, string $targetId, array $payload = [], ?BehavioralTracker $behavioralTracker = null): void
    {
        $this->trackEvent('click', $vertical, $targetId, $payload, 0.0, null, $behavioralTracker);
    }

    /**
     * Зафиксировать добавление в корзину.
     */
    protected function trackAddToCart(string $vertical, string $targetId, array $payload = [], ?BehavioralTracker $behavioralTracker = null): void
    {
        $this->trackEvent('add_to_cart', $vertical, $targetId, $payload, 0.0, null, $behavioralTracker);
    }

    /**
     * Зафиксировать покупку/заказ.
     */
    protected function trackPurchase(string $vertical, string $targetId, float $amount, array $payload = [], ?BehavioralTracker $behavioralTracker = null): void
    {
        $this->trackEvent('purchase', $vertical, $targetId, $payload, $amount, null, $behavioralTracker);
    }

    /**
     * Зафиксировать завершение заказа.
     */
    protected function trackOrderCompleted(string $vertical, string $orderId, float $amount, ?int $userId = null, ?BehavioralTracker $behavioralTracker = null): void
    {
        $this->trackEvent('order_completed', $vertical, $orderId, ['order_id' => $orderId], $amount, $userId, $behavioralTracker);
    }

    /**
     * Зафиксировать бронирование.
     */
    protected function trackBooking(string $vertical, string $bookingId, float $amount, array $payload = [], ?BehavioralTracker $behavioralTracker = null): void
    {
        $this->trackEvent('booking_confirmed', $vertical, $bookingId, $payload, $amount, null, $behavioralTracker);
    }

    /**
     * Получить метрики спроса для вертикали.
     */
    protected function getDemandMetrics(
        string $vertical,
        ?string $tenantId = null,
        ?CarbonImmutable $fromDate = null,
        ?CarbonImmutable $toDate = null,
        ?VerticalAnalyticsIntegrationService $analyticsIntegration = null
    ): array {
        $analyticsIntegration ??= app(VerticalAnalyticsIntegrationService::class);
        return $analyticsIntegration->getDemandMetrics($vertical, $tenantId, $fromDate, $toDate);
    }

    /**
     * Получить метрики продаж для вертикали.
     */
    protected function getSalesMetrics(
        string $vertical,
        ?string $tenantId = null,
        ?CarbonImmutable $fromDate = null,
        ?CarbonImmutable $toDate = null,
        ?VerticalAnalyticsIntegrationService $analyticsIntegration = null
    ): array {
        $analyticsIntegration ??= app(VerticalAnalyticsIntegrationService::class);
        return $analyticsIntegration->getSalesMetrics($vertical, $tenantId, $fromDate, $toDate);
    }

    /**
     * Получить метрики качества для вертикали.
     */
    protected function getQualityMetrics(
        string $vertical,
        ?string $tenantId = null,
        ?CarbonImmutable $fromDate = null,
        ?CarbonImmutable $toDate = null,
        ?VerticalAnalyticsIntegrationService $analyticsIntegration = null
    ): array {
        $analyticsIntegration ??= app(VerticalAnalyticsIntegrationService::class);
        return $analyticsIntegration->getQualityMetrics($vertical, $tenantId, $fromDate, $toDate);
    }

    /**
     * Получить публичные метрики для вертикали.
     */
    protected function getPublicMetrics(
        string $vertical,
        ?string $tenantId = null,
        ?CarbonImmutable $fromDate = null,
        ?CarbonImmutable $toDate = null,
        ?VerticalAnalyticsIntegrationService $analyticsIntegration = null
    ): array {
        $analyticsIntegration ??= app(VerticalAnalyticsIntegrationService::class);
        return $analyticsIntegration->getPublicMetrics($vertical, $tenantId, $fromDate, $toDate);
    }

    /**
     * Получить полный дашборд вертикали.
     */
    protected function getVerticalDashboard(
        string $vertical,
        ?string $tenantId = null,
        ?CarbonImmutable $fromDate = null,
        ?CarbonImmutable $toDate = null,
        ?VerticalAnalyticsIntegrationService $analyticsIntegration = null
    ): array {
        $analyticsIntegration ??= app(VerticalAnalyticsIntegrationService::class);
        return $analyticsIntegration->getVerticalDashboard($vertical, $tenantId, $fromDate, $toDate);
    }

    /**
     * Записать кастомную метрику.
     */
    protected function recordMetric(
        string $vertical,
        string $metricType,
        array $data,
        ?string $tenantId = null,
        ?VerticalAnalyticsIntegrationService $analyticsIntegration = null
    ): void {
        $analyticsIntegration ??= app(VerticalAnalyticsIntegrationService::class);
        $analyticsIntegration->recordMetric($vertical, $metricType, $data, $tenantId);
    }

    /**
     * Рассчитать RFM для пользователя.
     */
    protected function calculateUserRFM(int $userId, ?AnalyticsOrchestratorService $analyticsOrchestrator = null): array
    {
        $analyticsOrchestrator ??= app(AnalyticsOrchestratorService::class);
        return $analyticsOrchestrator->calculateRFM($userId);
    }

    /**
     * Запустить RFM-анализ для всех пользователей тенанта.
     */
    protected function runRFMAnalysis(?RFMService $rfmService = null): void
    {
        $rfmService ??= app(RFMService::class);
        $rfmService->calculateRFM();
    }
}
