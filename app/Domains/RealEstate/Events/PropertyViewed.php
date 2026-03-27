<?php

declare(strict_types=1);


namespace App\Domains\RealEstate\Events;

use App\Domains\RealEstate\Models\ViewingAppointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event для просмотра объекта.
 * Production 2026.
 */
final class PropertyViewed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ViewingAppointment $appointment,
        public readonly string $correlationId,
    ) {}
}
