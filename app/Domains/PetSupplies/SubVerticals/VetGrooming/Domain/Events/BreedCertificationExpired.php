<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\VetGrooming\Domain\Entities\BreedCertification;

final class BreedCertificationExpired
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly BreedCertification $certification,
        public readonly int $masterId,
        public readonly int $tenantId,
        public readonly ?string $breedGroup,
        public readonly ?string $breedId,
    ) {}
}
