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
use Illuminate\Support\Facades\Event as LaravelEvent;

final readonly class CaptureBehavioralEventUseCase
{
    public function __construct(
        private BehavioralEventRepositoryInterface $repository,
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

        // Dispatch domain event
        LaravelEvent::dispatch(new BehavioralEventCaptured(
            event: $savedEvent,
            userId: new UserId($savedEvent->userId),
            eventType: new EventType($savedEvent->eventType),
            capturedAt: Timestamp::fromString($savedEvent->occurredAt->toIso8601String()),
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
