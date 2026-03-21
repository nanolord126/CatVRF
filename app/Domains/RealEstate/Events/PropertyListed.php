<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event для размещения объявления.
 * Production 2026.
 */
final class PropertyListed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly mixed $listing, // RentalListing | SaleListing
        public readonly string $correlationId,
    ) {}
}
