<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Application\UseCases;


use Psr\Log\LoggerInterface;
use App\Domains\Analytics\Domain\Entities\AnalyticsEvent;
use App\Domains\Analytics\Domain\Events\AnalyticsEventTracked;
use App\Domains\Analytics\Domain\Interfaces\AnalyticsEventRepositoryInterface;
use Illuminate\Support\Str;

/**
 * Class TrackEventUseCase
 *
 * Part of the Analytics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Analytics\Application\UseCases
 */
final readonly class TrackEventUseCase
{
    public function __construct(
        private AnalyticsEventRepositoryInterface $repository, private readonly LoggerInterface $logger
    ) {
}

    public function execute(
        int $tenantId,
        ?int $userId,
        string $eventType,
        array $payload,
        string $vertical,
        ?string $ipAddress,
        ?string $deviceFingerprint,
        ?string $correlationId
    ): void {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $event = AnalyticsEvent::create(
            $tenantId,
            $userId,
            $eventType,
            $payload,
            $vertical,
            $ipAddress,
            $deviceFingerprint,
            $correlationId
        );

        // In a high-traffic environment, this should be a batch insert via a queue.
        $this->repository->save($event);

        event(new AnalyticsEventTracked($eventType, $payload, $correlationId));

        $this->logger->info('Analytics event tracked', [
            'event_type' => $eventType,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);
    }
}
