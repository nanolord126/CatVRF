<?php

declare(strict_types=1);

namespace Modules\RealEstate\Domain\Events;

use Modules\RealEstate\Domain\Entities\Property;

final readonly class PropertyStatusChanged
{
    public function __construct(
        public Property $property,
        public string $previousStatus,
        public string $newStatus,
    ) {
    }
}
