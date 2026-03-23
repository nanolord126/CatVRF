<?php

declare(strict_types=1);

namespace App\Domains\Auto\DTOs;

final readonly class LocationDTO
{
    public function __construct(
        public float $latitude,
        public float $longitude,
        public ?string $address = null,
    ) {
    }

    public function toPoint(): string
    {
        return "POINT({$this->longitude} {$this->latitude})";
    }
}
