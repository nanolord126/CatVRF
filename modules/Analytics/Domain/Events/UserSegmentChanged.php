<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Events;

use Modules\Analytics\Domain\ValueObjects\UserId;
use Modules\Analytics\Domain\ValueObjects\Timestamp;

final readonly class UserSegmentChanged
{
    public function __construct(
        public UserId $userId,
        public string $previousSegment,
        public string $newSegment,
        public Timestamp $changedAt,
    ) {
    }
}
