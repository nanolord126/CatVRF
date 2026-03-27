<?php

declare(strict_types=1);


namespace App\Domains\RealEstate\Events;

use App\Domains\RealEstate\Models\SaleListing;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event для продажи объекта.
 * Production 2026.
 */
final class PropertySold
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SaleListing $listing,
        public readonly string $correlationId,
    ) {}
}
