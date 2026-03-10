<?php

namespace App\Services\Hotels;

use Modules\Hotels\Models\Room as HotelRoom;
use Modules\Hotels\Models\Booking as HotelBooking;
use Illuminate\Support\Facades\DB;
use App\Services\Common\Security\AIAnomalyDetector;
use Carbon\Carbon;

class HotelPMSManager
{
    protected AIAnomalyDetector $detector;

    public function __construct(AIAnomalyDetector $detector)
    {
        $this->detector = $detector;
    }

    /**
     * Создание бронирования с проверкой доступности и фрода.
     */
    public function createBooking(int $customerId, int $roomTypeId, string $checkIn, string $checkOut): HotelBooking
    {
        $checkIn = Carbon::parse($checkIn);
        $checkOut = Carbon::parse($checkOut);
        
        // 1. Поиск ДОСТУПНОЙ, ЧИСТОЙ и НЕ НА РЕМОНТЕ комнаты этого типа (Hardened logic)
        $room = HotelRoom::where('room_type_id', $roomTypeId)
            ->where('status', 'available') // Только доступные
            ->where('is_dirty', false)    // Номер должен быть убран
            ->where('is_blocked', false)  // Не заблокирован админом
            ->whereDoesntHave('bookings', function ($q) use ($checkIn, $checkOut) {
                // Продвинутая проверка пересечения дат (учитывает Check-In/Out hours)
                $q->where(function ($query) use ($checkIn, $checkOut) {
                    $query->whereBetween('check_in', [$checkIn, $checkOut->subHour()])
                          ->orWhereBetween('check_out', [$checkIn->addHour(), $checkOut]);
                });
            })->first();

        if (!$room) {
            // Recommendation 2026: Предложить альтернативный тип номера вместо ошибки
            throw new \Exception("Подходящий номер не найден.");
        }

        // 2. Fraud Check (Velocity, Reputation)
        $risk = $this->detector->analyze(tenant(), $customerId, 'hotel_booking', [
            'room_id' => $room->id,
            'amount' => $room->price * $checkIn->diffInDays($checkOut)
        ]);

        if ($risk >= 80) {
            throw new \Exception("Бронирование заблокировано системой безопасности (Risk: $risk).");
        }

        return DB::transaction(function () use ($customerId, $room, $checkIn, $checkOut) {
            return HotelBooking::create([
                'customer_id' => $customerId,
                'room_id' => $room->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'total_price' => $room->price * $checkIn->diffInDays($checkOut),
                'status' => 'confirmed',
                'correlation_id' => (string) \Illuminate\Support\Str::uuid()
            ]);
        });
    }

    /**
     * Статус Housekeeping - Автоматическое назначение уборки после выезда.
     */
    public function checkOut(HotelBooking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'checked_out', 'check_out' => Carbon::now()]);
            $booking->room->update(['status' => 'cleaning']);
            
            // Здесь можно создать StaffTask для персонала через HR модуль.
        });
    }
}
