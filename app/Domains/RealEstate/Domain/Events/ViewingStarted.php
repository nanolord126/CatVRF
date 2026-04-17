<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Domain\Events;

use App\Domains\RealEstate\Models\ViewingAppointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ViewingStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ViewingAppointment $viewing,
        public readonly string $correlationId,
    ) {}
}
