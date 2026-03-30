<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StrAvailabilityService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    private const CACHE_PREFIX = 'str_availability:';

        public function __construct() {}

        /**
         * Проверка доступности апартамента на период
         */
        public function isAvailable(int $apartmentId, Carbon $from, Carbon $to): bool
        {
            $tenantId = tenant()->id ?? 0;
            $cacheKey = self::CACHE_PREFIX . "{$tenantId}:{$apartmentId}:" . $from->format('Y-m-d') . ':' . $to->format('Y-m-d');

            return Cache::remember($cacheKey, 300, function () use ($apartmentId, $from, $to) {
                // 1. Проверяем пересечения с существующими бронированиями
                $hasBookings = \App\Domains\ShortTermRentals\Models\StrBooking::where('apartment_id', $apartmentId)
                    ->whereIn('status', ['confirmed', 'active', 'completed'])
                    ->where(function ($query) use ($from, $to) {
                        $query->whereBetween('check_in', [$from, $to->copy()->subSecond()])
                            ->orWhereBetween('check_out', [$from->copy()->addSecond(), $to])
                            ->orWhere(function ($q) use ($from, $to) {
                                $q->where('check_in', '<=', $from)
                                    ->where('check_out', '>=', $to);
                            });
                    })->exists();

                if ($hasBookings) {
                    return false;
                }

                // 2. Проверяем блокировки в календаре
                $blocks = StrCalendarAvailability::where('apartment_id', $apartmentId)
                    ->whereBetween('date', [$from, $to->copy()->subDay()])
                    ->where('is_available', false)
                    ->exists();

                return !$blocks;
            });
        }

        /**
         * Получение цен на период (с учетом оверрайдов)
         */
        public function getPrices(int $apartmentId, Carbon $from, Carbon $to, bool $isB2B = false): Collection
        {
            $apartment = StrApartment::findOrFail($apartmentId);
            $period = CarbonPeriod::create($from, $to->copy()->subDay());

            $overrides = StrCalendarAvailability::where('apartment_id', $apartmentId)
                ->whereBetween('date', [$from, $to->copy()->subDay()])
                ->get()
                ->keyBy(fn($item) => $item->date->format('Y-m-d'));

            $result = collect();

            foreach ($period as $date) {
                $formattedDate = $date->format('Y-m-d');
                $price = $isB2B ? $apartment->base_price_b2b : $apartment->base_price_b2c;

                if (isset($overrides[$formattedDate])) {
                    $override = $overrides[$formattedDate];
                    $price = $isB2B
                        ? ($override->price_override_b2b ?? $price)
                        : ($override->price_override_b2c ?? $price);
                }

                $result->push([
                    'date' => $formattedDate,
                    'price' => $price,
                ]);
            }

            return $result;
        }

        /**
         * Блокировка дат в календаре
         */
        public function blockDates(int $apartmentId, Carbon $from, Carbon $to, string $reason, string $correlationId): void
        {
            $period = CarbonPeriod::create($from, $to->copy()->subDay());

            foreach ($period as $date) {
                StrCalendarAvailability::updateOrCreate(
                    [
                        'apartment_id' => $apartmentId,
                        'date' => $date->format('Y-m-d'),
                    ],
                    [
                        'is_available' => false,
                        'reason' => $reason,
                        'correlation_id' => $correlationId,
                    ]
                );
            }

            $this->invalidateCache($apartmentId);

            Log::channel('audit')->info('Dates blocked by system', [
                'apartment_id' => $apartmentId,
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);
        }

        public function invalidateCache(int $apartmentId): void
        {
            // В реальном проекте здесь будет очистка по тегам или паттерну
            // Для упрощения - ожидаем TTL 300 сек
        }
}
