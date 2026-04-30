<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Publisher Sub-Account Created Event
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final class PublisherSubAccountCreated
{
    use Dispatchable;

    public function __construct(
        public readonly int $subAccountId,
        public readonly int $publisherId,
        public readonly string $correlationId,
    ) {}
}
