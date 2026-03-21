<?php declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use App\Domains\Hotels\Models\RoomType;
use App\Domains\Hotels\Models\RoomInventory;
use App\Domains\Hotels\Models\PricingRule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Throwable;

final class PricingService
{
    public function calculateRoomPrice(
        int $roomTypeId,
        string $checkInDate,
        string $checkOutDate,
        string $correlationId = '',
    ): int {
        try {
            Log::channel('audit')->info('Calculating room price', [
                'room_type_id' => $roomTypeId,
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'correlation_id' => $correlationId,
            ]);

            $roomType = RoomType::findOrFail($roomTypeId);
            $checkInDt = Carbon::parse($checkInDate);
            $checkOutDt = Carbon::parse($checkOutDate);
            $nights = $checkInDt->diffInDays($checkOutDt);

            if ($nights <= 0) {
                throw new \Exception('Invalid dates');
            }

            $basePrice = $roomType->base_price_per_night;
            $totalPrice = 0;

            // Calculate price for each night with multipliers
            for ($i = 0; $i < $nights; $i++) {
                $date = $checkInDt->clone()->addDays($i);
                $multiplier = 1.0;

                // Get inventory record for this date
                $inventory = RoomInventory::where('room_type_id', $roomTypeId)
                    ->where('date', $date)
                    ->first();

                if ($inventory) {
                    $multiplier = $inventory->multiplier ?? 1.0;
                }

                $nightPrice = (int) ($basePrice * $multiplier);
                $totalPrice += $nightPrice;
            }

            Log::channel('audit')->info('Room price calculated', [
                'room_type_id' => $roomTypeId,
                'total_price' => $totalPrice,
                'correlation_id' => $correlationId,
            ]);

            return $totalPrice;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Price calculation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function getAvailableRooms(
        int $hotelId,
        string $checkInDate,
        string $checkOutDate,
        string $correlationId = '',
    ): array {
        try {
            Log::channel('audit')->info('Getting available rooms', [
                'hotel_id' => $hotelId,
                'check_in_date' => $checkInDate,
                'correlation_id' => $correlationId,
            ]);

            $checkInDt = Carbon::parse($checkInDate);
            $checkOutDt = Carbon::parse($checkOutDate);

            // Find all room types with available inventory for all nights
            $roomTypes = RoomType::where('hotel_id', $hotelId)
                ->with(['inventory' => function ($q) use ($checkInDt, $checkOutDt) {
                    $q->whereDate('date', '>=', $checkInDt)
                        ->whereDate('date', '<', $checkOutDt)
                        ->where('available_count', '>', 0);
                }])
                ->get()
                ->filter(fn ($rt) => $rt->inventory->count() > 0)
                ->toArray();

            Log::channel('audit')->info('Available rooms found', [
                'count' => count($roomTypes),
                'correlation_id' => $correlationId,
            ]);

            return $roomTypes;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to get available rooms', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function applySeasonalMultiplier(
        int $roomTypeId,
        string $date,
        string $correlationId = '',
    ): float {
        try {
            $dateParsed = Carbon::parse($date);

            // Get active pricing rules
            $rules = PricingRule::where('room_type_id', $roomTypeId)
                ->where('is_active', true)
                ->where(function ($q) use ($dateParsed) {
                    $q->whereNull('date_from')
                        ->orWhere('date_from', '<=', $dateParsed)
                        ->where(function ($qq) use ($dateParsed) {
                            $qq->whereNull('date_to')
                                ->orWhere('date_to', '>=', $dateParsed);
                        });
                })
                ->get();

            $multiplier = 1.0;

            foreach ($rules as $rule) {
                $multiplier *= $rule->multiplier;
            }

            return $multiplier;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to apply seasonal multiplier', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
