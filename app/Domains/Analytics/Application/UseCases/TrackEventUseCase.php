<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Application\UseCases;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use App\Services\Fraud\FraudControlService;

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
 */
final readonly class TrackEventUseCase
{
    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly FraudControlService $fraudControlService,
        private readonly AnalyticsEventRepositoryInterface $repository,
        private readonly LoggerInterface $logger) {}

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
        $this->fraudControlService->check('execute', ['context' => __CLASS__]);
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

        $this->eventDispatcher->dispatch(new AnalyticsEventTracked($eventType, $payload, $correlationId));

        $this->logger->$this->logger->info('Analytics event tracked', [
            'event_type' => $eventType,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);
    }
}
