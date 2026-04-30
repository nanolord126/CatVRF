<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\UseCases;

use Modules\Analytics\Application\DTOs\BehavioralEventDto;
use Modules\Analytics\Domain\Entities\BehavioralEvent;
use Modules\Analytics\Domain\Events\BehavioralEventCaptured;
use Modules\Analytics\Domain\Repositories\BehavioralEventRepositoryInterface;
use Modules\Analytics\Domain\Exceptions\InvalidEventPayloadException;
use Modules\Analytics\Domain\ValueObjects\EventType;
use Modules\Analytics\Domain\ValueObjects\Timestamp;
use Modules\Analytics\Domain\ValueObjects\UserId;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class CaptureBehavioralEventUseCase
{
    public function __construct(
        private BehavioralEventRepositoryInterface $repository,
        private readonly Dispatcher $eventDispatcher,
    ) {
    }

    public function execute(BehavioralEventDto $dto): BehavioralEvent
    {
        $this->validatePayload($dto);

        $event = BehavioralEvent::create(
            userId: $dto->userId,
            tenantId: $dto->tenantId ?? 1, // Default tenant if not provided
            eventType: $dto->eventType,
            entityType: $dto->entityType,
            entityId: $dto->entityId,
            metadata: $dto->eventData,
        );

        $savedEvent = $this->repository->save($event);

        if ($savedEvent === null) {
            throw new \RuntimeException('Failed to save behavioral event');
        }

        // Dispatch domain event
        $this->eventDispatcher->dispatch(new BehavioralEventCaptured(
            event: $savedEvent,
            userId: new UserId($savedEvent->userId ?? 0),
            eventType: new EventType($savedEvent->eventType ?? 'unknown'),
            capturedAt: Timestamp::fromString($savedEvent->occurredAt?->toIso8601String() ?? now()->toIso8601String()),
        ));

        return $savedEvent;
    }

    private function validatePayload(BehavioralEventDto $dto): void
    {
        if ($dto->userId <= 0) {
            throw new InvalidEventPayloadException('User ID must be positive');
        }

        if (empty($dto->eventType)) {
            throw new InvalidEventPayloadException('Event type is required');
        }
    }
}
