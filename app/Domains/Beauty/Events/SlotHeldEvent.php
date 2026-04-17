<?php declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use App\Domains\Beauty\Models\BookingSlot;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class SlotHeldEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BookingSlot $slot,
        public string $correlationId,
    ) {
    }
}
