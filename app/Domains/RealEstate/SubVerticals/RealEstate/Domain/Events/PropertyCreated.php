<?php

declare(strict_types=1);

namespace Modules\RealEstate\Domain\Events;

use Modules\RealEstate\Domain\Entities\Property;

final readonly class PropertyCreated
{
    public function __construct(
        public Property $property,
    ) {
    }
}
