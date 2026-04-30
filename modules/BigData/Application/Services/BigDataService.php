<?php

declare(strict_types=1);

namespace Modules\BigData\Application\Services;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\DTOs\BaseEventDTO;
use Modules\BigData\Domain\DTOs\EventValidator;
use Modules\BigData\Domain\Enums\EventType;
use Modules\BigData\Infrastructure\ClickHouse\ClickHouseService;
use Modules\BigData\Infrastructure\Kafka\KafkaProducerService;
use Psr\Log\LoggerInterface;

/**
 * Big Data Service (Orchestrator)
 *
 * Main service for Big Data operations:
 * - Event tracking (Kafka + ClickHouse)
 * - Metrics queries (Seller analytics, GMV, CLV)
 * - A/B test analysis
 * - Feature store queries
 */
final readonly class BigDataService
{
    public function __construct(
        private readonly KafkaProducerService $kafkaProducer,
        private readonly ClickHouseService $clickHouse,
        private readonly EventValidator $validator,
        private readonly LoggerInterface $logger,
        private readonly bool $enableKafka = true,
    ) {}

    /**
     * Track an event (main entry point)
     *
     * Validates event and sends to Kafka (if enabled) and ClickHouse.
     */
    public function track(BaseEventDTO $event): bool
    {
        try {
            // Validate event
            $this->validator->validate($event);

            // Anonymize if PII-sensitive
            $eventToTrack = $event->eventType->isPIISensitive() ? $event->anonymize() : $event;

            // Send to Kafka for real-time processing
            if ($this->enableKafka) {
                $kafkaSuccess = $this->kafkaProducer->publish($eventToTrack);
                if (!$kafkaSuccess) {
                    $this->logger->warning('Kafka publish failed, falling back to direct ClickHouse insert', [
                        'event_id' => $event->eventId,
                    ]);
                    $this->clickHouse->insertEvent($eventToTrack);
                }
            } else {
                // Direct insert to ClickHouse if Kafka is disabled
                $this->clickHouse->insertEvent($eventToTrack);
            }

            $this->logger->debug('Event tracked successfully', [
                'event_id' => $event->eventId,
                'event_type' => $event->eventType->value,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to track event', [
                'error' => $event->eventType->value,
                'event_id' => $event->eventId,
                'error_msg' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Track multiple events in batch
     *
     * @param array<BaseEventDTO> $events
     */
    public function trackBatch(array $events): int
    {
        $successCount = 0;

        foreach ($events as $event) {
            if ($this->track($event)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Convenience method: track event with parameters
     */
    public function trackEvent(
        EventType $eventType,
        int $tenantId,
        ?int $userId = null,
        ?int $sellerId = null,
        ?int $productId = null,
        ?int $orderId = null,
        ?string $sessionId = null,
        ?string $vertical = null,
        array $properties = [],
        ?float $monetaryValue = null,
        array $context = [],
    ): bool {
        $event = BaseEventDTO::create(
            eventType: $eventType,
            tenantId: $tenantId,
            userId: $userId,
            sellerId: $sellerId,
            productId: $productId,
            orderId: $orderId,
            sessionId: $sessionId,
            vertical: $vertical,
            properties: $properties,
            monetaryValue: $monetaryValue,
            context: $context,
        );

        return $this->track($event);
    }

    // ============================================================================
    // QUERY METHODS (ClickHouse)
    // ============================================================================

    /**
     * Get seller metrics for a period
     */
    public function getSellerMetrics(
        int $tenantId,
        int $sellerId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): array {
        return $this->clickHouse->getSellerMetrics($tenantId, $sellerId, $startDate, $endDate);
    }

    /**
     * Get seller summary (aggregated metrics)
     */
    public function getSellerSummary(int $tenantId, int $sellerId, int $days = 30): array
    {
        return $this->clickHouse->getSellerSummary($tenantId, $sellerId, $days);
    }

    /**
     * Get daily GMV for dashboard
     */
    public function getDailyGMV(int $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return $this->clickHouse->getDailyGMV($tenantId, $startDate, $endDate);
    }

    /**
     * Get CLV prediction for a user
     */
    public function getCLVPrediction(int $tenantId, int $userId): ?array
    {
        return $this->clickHouse->getCLVPrediction($tenantId, $userId);
    }

    /**
     * Get top sellers by GMV
     */
    public function getTopSellersByGMV(int $tenantId, int $days = 30, int $limit = 10): array
    {
        return $this->clickHouse->getTopSellersByGMV($tenantId, $days, $limit);
    }

    /**
     * Get A/B test results
     */
    public function getABTestResults(string $testId, int $tenantId): array
    {
        return $this->clickHouse->getABTestResults($testId, $tenantId);
    }

    /**
     * Get event counts by type (for monitoring)
     */
    public function getEventCounts(int $tenantId, CarbonImmutable $since): array
    {
        return $this->clickHouse->getEventCountsByType($tenantId, $since);
    }

    /**
     * Get buyer-seller affinity features
     */
    public function getBuyerSellerAffinity(int $tenantId, int $buyerId, int $sellerId): ?array
    {
        return $this->clickHouse->getBuyerSellerFeatures($tenantId, $buyerId, $sellerId);
    }

    /**
     * Execute custom query (admin only)
     */
    public function executeQuery(string $query, array $params = []): array
    {
        return $this->clickHouse->executeCustomQuery($query, $params);
    }

    /**
     * Health check
     */
    public function healthCheck(): array
    {
        return $this->clickHouse->healthCheck();
    }
}
