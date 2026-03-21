<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\TaxiRide;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: поездка завершена.
 * Production 2026.
 */
final class RideCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        readonly public TaxiRide $ride,
        readonly public string $correlationId = '',
    ) {
    }
}
