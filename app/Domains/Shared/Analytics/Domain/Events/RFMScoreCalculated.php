<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Events;

use Modules\Analytics\Domain\ValueObjects\UserId;
use Modules\Analytics\Domain\ValueObjects\Timestamp;

final readonly class RFMScoreCalculated
{
    public function __construct(
        public UserId $userId,
        public int $recencyScore,
        public int $frequencyScore,
        public int $monetaryScore,
        public float $overallScore,
        public Timestamp $calculatedAt,
    ) {
    }
}
