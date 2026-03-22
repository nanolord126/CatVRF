<?php

declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Services;

use App\Domains\ShortTermRentals\Models\ApartmentBooking;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class ApartmentBookingService
{
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function createBooking(array $data, bool $isB2B = false): ApartmentBooking
    {
        $correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

        Log::channel('audit')->info('Apartment booking started', [
            'correlation_id' => $correlationId,
            'is_b2b' => $isB2B,
        ]);

        $this->fraudControlService->check([
            'operation' => 'apartment_booking',
            'user_id' => $data['user_id'] ?? null,
            'correlation_id' => $correlationId,
        ]);

        return DB::transaction(function () use ($data, $correlationId) {
            $booking = ApartmentBooking::create([
                ...$data,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $correlationId,
                'status' => 'pending',
            ]);

            Log::channel('audit')->info('Apartment booking created', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }
}
