<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Events;

use Modules\Analytics\Domain\Entities\BehavioralEvent;
use Modules\Analytics\Domain\ValueObjects\EventType;
use Modules\Analytics\Domain\ValueObjects\UserId;
use Modules\Analytics\Domain\ValueObjects\Timestamp;

final readonly class BehavioralEventCaptured
{
    public function __construct(
        public BehavioralEvent $event,
        public UserId $userId,
        public EventType $eventType,
        public Timestamp $capturedAt,
    ) {
    }
}
