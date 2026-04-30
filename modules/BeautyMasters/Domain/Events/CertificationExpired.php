<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class CertificationExpired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $masterId,
        public string $vertical,
        public string $certificationName,
        public \DateTimeImmutable $expiryDate,
    ) {
    }
}
