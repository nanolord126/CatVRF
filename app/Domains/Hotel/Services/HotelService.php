<?php

namespace App\Domains\Hotel\Services;

use App\Domains\Hotel\Models\{Hotel, HotelBooking, HotelRoom};
use App\Models\AuditLog;
use Illuminate\Support\Facades\{DB, Log, Cache};
use Illuminate\Support\Str;
use Throwable;

/**
 * HotelService - Сервис управления гостиницами и бронированием (Production 2026).
 *
 * Отвечает за:
 * - Проверку доступности номеров
 * - Расчет стоимости проживания
 * - Управление бронированиями
 * - Интеграцию с платежами и кошельком
 *
 * @package App\Domains\Hotel\Services
 */
class HotelService
{
    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
    }

    /**
     * Получить доступные номера на определенные даты.
     *
     * @param int $hotelId ID гостиницы
     * @param string $checkIn Дата заезда (YYYY-MM-DD)
     * @param string $checkOut Дата выезда (YYYY-MM-DD)
     * @return array Список доступных номеров с ценами
     */
    public function getAvailableRooms(int $hotelId, string $checkIn, string $checkOut): array
    {
        try {
            $checkInDate = \Carbon\Carbon::parse($checkIn)->startOfDay();
            $checkOutDate = \Carbon\Carbon::parse($checkOut)->startOfDay();

            if ($checkOutDate <= $checkInDate) {
                throw new \InvalidArgumentException('Дата выезда должна быть позже даты заезда');
            }

            return Cache::remember("hotel_available_{$hotelId}_{$checkIn}_{$checkOut}", 3600, function () use ($hotelId, $checkInDate, $checkOutDate) {
                $hotel = Hotel::find($hotelId);
                if (!$hotel) {
                    return [];
                }

                // Получить все номера отеля
                $allRooms = $hotel->rooms()->where('is_active', true)->get();

                // Найти забронированные номера на эти даты
                $bookedRoomIds = HotelBooking::where('hotel_id', $hotelId)
                    ->whereIn('status', ['confirmed', 'checked_in'])
                    ->where(function ($query) use ($checkInDate, $checkOutDate) {
                        $query->whereBetween('check_in_date', [$checkInDate, $checkOutDate])
                            ->orWhereBetween('check_out_date', [$checkInDate, $checkOutDate])
                            ->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                                $q->where('check_in_date', '<=', $checkInDate)
                                    ->where('check_out_date', '>=', $checkOutDate);
                            });
                    })
                    ->pluck('hotel_room_id')
                    ->toArray();

                // Вернуть доступные номера
                return $allRooms->reject(function ($room) use ($bookedRoomIds) {
                    return in_array($room->id, $bookedRoomIds);
                })->map(function ($room) use ($checkInDate, $checkOutDate) {
                    $nights = $checkOutDate->diffInDays($checkInDate);
                    $totalPrice = (float)$room->price_per_night * $nights;

                    return [
                        'id' => $room->id,
                        'number' => $room->room_number,
                        'type' => $room->room_type,
                        'capacity' => $room->capacity,
                        'price_per_night' => $room->price_per_night,
                        'total_price' => round($totalPrice, 2),
                        'nights' => $nights,
                        'amenities' => $room->amenities ?? [],
                    ];
                })->values()->toArray();
            });
        } catch (Throwable $e) {
            Log::error('Failed to get available rooms', [
                'hotel_id' => $hotelId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            return [];
        }
    }

    /**
     * Создать новое бронирование.
     *
     * @param array $data Данные бронирования
     *   - hotel_id: ID гостиницы
     *   - room_id: ID номера
     *   - check_in_date: дата заезда
     *   - check_out_date: дата выезда
     *   - guest_name: имя гостя
     *   - guest_email: email
     *   - guest_phone: телефон
     *   - special_requests: специальные пожелания
     * @return HotelBooking
     */
    public function createBooking(array $data): HotelBooking
    {
        return DB::transaction(function () use ($data) {
            $checkInDate = \Carbon\Carbon::parse($data['check_in_date'])->startOfDay();
            $checkOutDate = \Carbon\Carbon::parse($data['check_out_date'])->startOfDay();

            // Проверить доступность номера
            $conflict = HotelBooking::where('hotel_room_id', $data['room_id'])
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->where(function ($query) use ($checkInDate, $checkOutDate) {
                    $query->whereBetween('check_in_date', [$checkInDate, $checkOutDate])
                        ->orWhereBetween('check_out_date', [$checkInDate, $checkOutDate])
                        ->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                            $q->where('check_in_date', '<=', $checkInDate)
                                ->where('check_out_date', '>=', $checkOutDate);
                        });
                })
                ->exists();

            if ($conflict) {
                throw new \InvalidArgumentException('Номер уже забронирован на эти даты');
            }

            // Получить цену номера
            $room = HotelRoom::find($data['room_id']);
            if (!$room) {
                throw new \InvalidArgumentException('Номер не найден');
            }

            $nights = $checkOutDate->diffInDays($checkInDate);
            $totalPrice = (float)$room->price_per_night * $nights;

            // Создать бронирование
            $booking = HotelBooking::create([
                'hotel_id' => $data['hotel_id'],
                'hotel_room_id' => $data['room_id'],
                'user_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'guest_name' => $data['guest_name'],
                'guest_email' => $data['guest_email'],
                'guest_phone' => $data['guest_phone'],
                'total_price' => $totalPrice,
                'status' => 'pending',
                'special_requests' => $data['special_requests'] ?? null,
                'correlation_id' => $this->correlationId,
            ]);

            AuditLog::create([
                'model_type' => HotelBooking::class,
                'model_id' => $booking->id,
                'action' => 'created',
                'old_values' => [],
                'new_values' => $booking->toArray(),
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'correlation_id' => $this->correlationId,
            ]);

            Log::info('Hotel booking created', [
                'booking_id' => $booking->id,
                'total_price' => $totalPrice,
                'correlation_id' => $this->correlationId,
            ]);

            return $booking;
        });
    }

    /**
     * Подтвердить бронирование и выполнить платеж.
     *
     * @param HotelBooking $booking
     * @return bool
     */
    public function confirmBooking(HotelBooking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            if ($booking->status !== 'pending') {
                throw new \InvalidArgumentException('Бронирование уже обработано');
            }

            $booking->update([
                'status' => 'confirmed',
                'confirmation_date' => now(),
            ]);

            AuditLog::create([
                'model_type' => HotelBooking::class,
                'model_id' => $booking->id,
                'action' => 'confirmed',
                'old_values' => ['status' => 'pending'],
                'new_values' => ['status' => 'confirmed'],
                'user_id' => auth()->id(),
                'correlation_id' => $this->correlationId,
            ]);

            Log::info('Hotel booking confirmed', [
                'booking_id' => $booking->id,
                'correlation_id' => $this->correlationId,
            ]);

            return true;
        });
    }

    /**
     * Выполнить check-in (заезд).
     *
     * @param HotelBooking $booking
     * @return bool
     */
    public function checkIn(HotelBooking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            if ($booking->status !== 'confirmed') {
                throw new \InvalidArgumentException('Бронирование не подтверждено');
            }

            if ($booking->check_in_date->startOfDay() > now()->startOfDay()) {
                throw new \InvalidArgumentException('Check-in возможен только в дату заезда или позже');
            }

            $booking->update([
                'status' => 'checked_in',
                'checked_in_at' => now(),
            ]);

            AuditLog::create([
                'model_type' => HotelBooking::class,
                'model_id' => $booking->id,
                'action' => 'checked_in',
                'old_values' => ['status' => 'confirmed'],
                'new_values' => ['status' => 'checked_in'],
                'user_id' => auth()->id(),
                'correlation_id' => $this->correlationId,
            ]);

            Log::info('Hotel check-in performed', [
                'booking_id' => $booking->id,
                'correlation_id' => $this->correlationId,
            ]);

            return true;
        });
    }

    /**
     * Выполнить check-out (выезд).
     *
     * @param HotelBooking $booking
     * @return bool
     */
    public function checkOut(HotelBooking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            if ($booking->status !== 'checked_in') {
                throw new \InvalidArgumentException('Гость еще не заехал');
            }

            $booking->update([
                'status' => 'completed',
                'checked_out_at' => now(),
            ]);

            AuditLog::create([
                'model_type' => HotelBooking::class,
                'model_id' => $booking->id,
                'action' => 'completed',
                'old_values' => ['status' => 'checked_in'],
                'new_values' => ['status' => 'completed'],
                'user_id' => auth()->id(),
                'correlation_id' => $this->correlationId,
            ]);

            Log::info('Hotel check-out performed', [
                'booking_id' => $booking->id,
                'correlation_id' => $this->correlationId,
            ]);

            return true;
        });
    }

    /**
     * Отменить бронирование.
     *
     * @param HotelBooking $booking
     * @param string $reason
     * @return bool
     */
    public function cancelBooking(HotelBooking $booking, string $reason): bool
    {
        return DB::transaction(function () use ($booking, $reason) {
            if (!in_array($booking->status, ['pending', 'confirmed'])) {
                throw new \InvalidArgumentException('Бронирование не может быть отменено в статусе ' . $booking->status);
            }

            $oldStatus = $booking->status;
            $booking->update([
                'status' => 'cancelled',
                'cancellation_date' => now(),
                'cancellation_reason' => $reason,
            ]);

            AuditLog::create([
                'model_type' => HotelBooking::class,
                'model_id' => $booking->id,
                'action' => 'cancelled',
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'cancelled', 'reason' => $reason],
                'user_id' => auth()->id(),
                'correlation_id' => $this->correlationId,
            ]);

            Log::info('Hotel booking cancelled', [
                'booking_id' => $booking->id,
                'reason' => $reason,
                'correlation_id' => $this->correlationId,
            ]);

            return true;
        });
    }

    /**
     * Получить статистику по отелю за период.
     *
     * @param int $hotelId
     * @param int $daysAgo
     * @return array
     */
    public function getStatistics(int $hotelId, int $daysAgo = -30): array
    {
        try {
            $fromDate = now()->addDays($daysAgo)->startOfDay();

            return Cache::remember("hotel_stats_{$hotelId}_{$daysAgo}", 3600, function () use ($hotelId, $fromDate) {
                $bookings = HotelBooking::where('hotel_id', $hotelId)
                    ->where('created_at', '>=', $fromDate)
                    ->get();

                $completed = $bookings->where('status', 'completed');
                $revenue = $completed->sum('total_price');
                $nights = $completed->sum(function ($b) {
                    return $b->check_out_date->diffInDays($b->check_in_date);
                });

                return [
                    'total_bookings' => $bookings->count(),
                    'confirmed_bookings' => $bookings->where('status', 'confirmed')->count(),
                    'completed_bookings' => $completed->count(),
                    'cancelled_bookings' => $bookings->where('status', 'cancelled')->count(),
                    'total_revenue' => round($revenue, 2),
                    'avg_booking_value' => round($revenue / ($completed->count() ?: 1), 2),
                    'total_nights' => $nights,
                    'occupancy_rate' => $this->calculateOccupancyRate($hotelId, $fromDate),
                ];
            });
        } catch (Throwable $e) {
            Log::error('Failed to get hotel statistics', [
                'hotel_id' => $hotelId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            return [];
        }
    }

    /**
     * Рассчитать процент заполнения (occupancy rate).
     *
     * @param int $hotelId
     * @param \DateTime $fromDate
     * @return float
     */
    private function calculateOccupancyRate(int $hotelId, \DateTime $fromDate): float
    {
        try {
            $hotel = Hotel::find($hotelId);
            $totalRooms = $hotel->rooms()->count();

            if ($totalRooms === 0) {
                return 0.0;
            }

            $bookings = HotelBooking::where('hotel_id', $hotelId)
                ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                ->where('created_at', '>=', $fromDate)
                ->get();

            $totalNights = 0;
            foreach ($bookings as $booking) {
                $totalNights += $booking->check_out_date->diffInDays($booking->check_in_date);
            }

            $daysInPeriod = now()->diffInDays($fromDate);
            $totalAvailableNights = $totalRooms * $daysInPeriod;

            if ($totalAvailableNights === 0) {
                return 0.0;
            }

            return round(($totalNights / $totalAvailableNights) * 100, 2);
        } catch (Throwable $e) {
            Log::error('Failed to calculate occupancy rate', [
                'hotel_id' => $hotelId,
                'error' => $e->getMessage(),
            ]);
            return 0.0;
        }
    }
}
