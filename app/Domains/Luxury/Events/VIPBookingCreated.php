<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Events;

use App\Domains\Luxury\Models\VIPBooking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * VIPBookingCreated
 *
 * Layer 6: Events & Listeners
 * Сообщает другим слоям (уведомления, почта, аналитика) о создании бронирования.
 *
 * @version 1.0.0
 * @author CatVRF
 */
final class VIPBookingCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public VIPBooking $booking,
        public string $correlationId
    ) {}
}
