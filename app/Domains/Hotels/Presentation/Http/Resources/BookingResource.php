<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Presentation\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * BookingResource — API Resource для представления данных бронирования (B2C).
 *
 * Форматирует доменную сущность Booking для JSON-ответа.
 * Включает вычисляемые поля: количество ночей, итоговая цена в рублях,
 * дедлайн бесплатной отмены, локализованный статус.
 *
 * @package App\Domains\Hotels\Presentation\Http\Resources
 */
final class BookingResource extends JsonResource
{
    /**
     * Количество часов до заезда, в течение которых отмена бесплатна.
     */
    private const FREE_CANCELLATION_HOURS = 24;

    /**
     * Преобразует Booking-сущность в массив для JSON-ответа.
     * Добавляет вычисляемые поля для удобства клиента.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $checkIn  = $this->getCheckInDate();
        $checkOut = $this->getCheckOutDate();
        $nights   = $checkIn->diffInDays($checkOut);

        return [
            'id'                    => $this->getId()->toString(),
            'hotel_id'              => $this->getHotelId()->toString(),
            'room_id'               => $this->getRoomId()->toString(),
            'user_id'               => $this->getUserId(),
            'check_in_date'         => $checkIn->toIso8601String(),
            'check_in_date_human'   => $checkIn->translatedFormat('d месяца Y'),
            'check_out_date'        => $checkOut->toIso8601String(),
            'check_out_date_human'  => $checkOut->translatedFormat('d месяца Y'),
            'nights_count'          => $nights,
            'nights_label'          => $this->formatNightsLabel((int) $nights),
            'total_price'           => $this->getTotalPrice(),
            'total_price_rub'       => $this->formatPriceInRubles($this->getTotalPrice()),
            'status'                => $this->getStatus()->value,
            'status_label'          => $this->getStatusLabel(),
            'can_cancel'            => $this->canCancelForFree($checkIn),
            'cancellation_deadline' => $checkIn->subHours(self::FREE_CANCELLATION_HOURS)->toIso8601String(),
            'correlation_id'        => $this->getCorrelationId(),
        ];
    }

    /**
     * Добавляет мета-данные к ответу.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version'      => '2026.04',
                'generated_at' => Carbon::now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Формирует подпись для количества ночей (склонение).
     */
    private function formatNightsLabel(int $nights): string
    {
        $mod10  = $nights % 10;
        $mod100 = $nights % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return "{$nights} ночь";
        }

        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return "{$nights} ночи";
        }

        return "{$nights} ночей";
    }

    /**
     * Форматирует цену в рублях из копеек.
     */
    private function formatPriceInRubles(int $priceKopecks): string
    {
        return number_format($priceKopecks / 100, 2, '.', ' ') . ' ₽';
    }

    /**
     * Возвращает локализованный статус бронирования.
     */
    private function getStatusLabel(): string
    {
        return match ($this->getStatus()->value) {
            'confirmed' => 'Подтверждено',
            'cancelled' => 'Отменено',
            'completed' => 'Завершено',
            'no_show'   => 'Неявка',
            default     => $this->getStatus()->value,
        };
    }

    /**
     * Проверяет, возможна ли бесплатная отмена (если до заезда больше FREE_CANCELLATION_HOURS).
     */
    private function canCancelForFree(Carbon $checkIn): bool
    {
        return $checkIn->diffInHours(Carbon::now(), false) < -self::FREE_CANCELLATION_HOURS;
    }
}
