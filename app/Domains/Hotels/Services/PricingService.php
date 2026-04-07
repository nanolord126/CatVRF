<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use App\Domains\Hotels\Models\PricingRule;
use App\Domains\Hotels\Models\RoomInventory;
use App\Domains\Hotels\Models\RoomType;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Сервис ценообразования для вертикали Hotels.
 *
 * Layer 3: Services — CatVRF 2026.
 * Расчёт цен за ночь с учётом сезонных мультипликаторов
 * и доступности номеров.
 *
 * @package App\Domains\Hotels\Services
 */
final readonly class PricingService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * Рассчитать стоимость номера за период пребывания.
     *
     * @param int $roomTypeId ID типа номера
     * @param string $checkInDate Дата заезда (Y-m-d)
     * @param string $checkOutDate Дата выезда (Y-m-d)
     * @param string $correlationId ID корреляции
     *
     * @return int Итоговая стоимость в копейках
     */
    public function calculateRoomPrice(
        int $roomTypeId,
        string $checkInDate,
        string $checkOutDate,
        string $correlationId = '',
    ): int {
        try {
            $this->logger->info('Calculating room price', [
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
                throw new \DomainException('Дата выезда должна быть позже даты заезда.');
            }

            $basePrice = (int) $roomType->base_price_per_night;
            $totalPrice = 0;

            // Расчёт цены за каждую ночь с учётом мультипликаторов
            for ($i = 0; $i < $nights; $i++) {
                $date = $checkInDt->clone()->addDays($i);

                $inventory = RoomInventory::where('room_type_id', $roomTypeId)
                    ->where('date', $date->toDateString())
                    ->first();

                $multiplier = ($inventory !== null) ? ((float) ($inventory->multiplier ?? 1.0)) : 1.0;
                $nightPrice = (int) ($basePrice * $multiplier);
                $totalPrice += $nightPrice;
            }

            $this->logger->info('Room price calculated', [
                'room_type_id' => $roomTypeId,
                'total_price' => $totalPrice,
                'nights' => $nights,
                'correlation_id' => $correlationId,
            ]);

            return $totalPrice;
        } catch (Throwable $e) {
            $this->logger->error('Price calculation failed', [
                'error' => $e->getMessage(),
                'room_type_id' => $roomTypeId,
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Получить доступные типы номеров в отеле на указанные даты.
     *
     * @param int $hotelId ID отеля
     * @param string $checkInDate Дата заезда (Y-m-d)
     * @param string $checkOutDate Дата выезда (Y-m-d)
     * @param string $correlationId ID корреляции
     *
     * @return array<int, array<string, mixed>> Массив доступных типов номеров
     */
    public function getAvailableRooms(
        int $hotelId,
        string $checkInDate,
        string $checkOutDate,
        string $correlationId = '',
    ): array {
        try {
            $this->logger->info('Getting available rooms', [
                'hotel_id' => $hotelId,
                'check_in_date' => $checkInDate,
                'correlation_id' => $correlationId,
            ]);

            $checkInDt = Carbon::parse($checkInDate);
            $checkOutDt = Carbon::parse($checkOutDate);

            $roomTypes = RoomType::where('hotel_id', $hotelId)
                ->with(['inventory' => function (object $q) use ($checkInDt, $checkOutDt): void {
                    $q->whereDate('date', '>=', $checkInDt)
                        ->whereDate('date', '<', $checkOutDt)
                        ->where('available_count', '>', 0);
                }])
                ->get()
                ->filter(fn(object $rt): bool => $rt->inventory->count() > 0)
                ->values()
                ->toArray();

            $this->logger->info('Available rooms found', [
                'hotel_id' => $hotelId,
                'count' => count($roomTypes),
                'correlation_id' => $correlationId,
            ]);

            return $roomTypes;
        } catch (Throwable $e) {
            $this->logger->error('Failed to get available rooms', [
                'error' => $e->getMessage(),
                'hotel_id' => $hotelId,
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Рассчитать сезонный мультипликатор цены на указанную дату.
     *
     * @param int $roomTypeId ID типа номера
     * @param string $date Дата (Y-m-d)
     * @param string $correlationId ID корреляции
     *
     * @return float Итоговый мультипликатор (>= 1.0)
     */
    public function applySeasonalMultiplier(
        int $roomTypeId,
        string $date,
        string $correlationId = '',
    ): float {
        try {
            $dateParsed = Carbon::parse($date);

            $rules = PricingRule::where('room_type_id', $roomTypeId)
                ->where('is_active', true)
                ->where(function (object $q) use ($dateParsed): void {
                    $q->whereNull('date_from')
                        ->orWhere('date_from', '<=', $dateParsed)
                        ->where(function (object $qq) use ($dateParsed): void {
                            $qq->whereNull('date_to')
                                ->orWhere('date_to', '>=', $dateParsed);
                        });
                })
                ->get();

            $multiplier = 1.0;

            foreach ($rules as $rule) {
                $multiplier *= (float) $rule->multiplier;
            }

            return $multiplier;
        } catch (Throwable $e) {
            $this->logger->error('Failed to apply seasonal multiplier', [
                'error' => $e->getMessage(),
                'room_type_id' => $roomTypeId,
                'date' => $date,
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }
}
