<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * RoomResource — API Resource для представления данных номера отеля (B2C).
 *
 * Форматирует доменную сущность Room для JSON-ответа.
 * Включает: тип номера, цену (в копейках и рублях), вместимость,
 * удобства, доступность, политику бронирования.
 *
 * @package App\Domains\Hotels\Presentation\Http\Resources
 */
final class RoomResource extends JsonResource
{
    /**
     * Количество секунд до заезда, после которых бронирование бесплатно отменяется.
     */
    private const FREE_CANCELLATION_HOURS = 24;

    /**
     * Преобразует Room-сущность в массив для JSON-ответа.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->getId()->toString(),
            'type'                  => $this->getType()->value,
            'type_label'            => $this->getTypeLabel(),
            'price_per_night'       => $this->getPricePerNight(),
            'price_per_night_rub'   => $this->formatPriceInRubles($this->getPricePerNight()),
            'capacity'              => $this->getCapacity(),
            'amenities'             => $this->getAmenities()->toArray(),
            'amenities_count'       => $this->getAmenities()->count(),
            'is_available'          => $this->isAvailable(),
            'availability_label'    => $this->isAvailable() ? 'Доступен' : 'Занят',
            'booking_policy'        => $this->buildBookingPolicy(),
        ];
    }

    /**
     * Возвращает локализованное название типа номера.
     */
    private function getTypeLabel(): string
    {
        return match ($this->getType()->value) {
            'double'  => 'Двухместный',
            'suite'   => 'Люкс',
            'deluxe'  => 'Делюкс',
            'family'  => 'Семейный',
            default   => $this->getType()->value,
        };
    }

    /**
     * Форматирует цену в рублях из копеек.
     * Все суммы в системе хранятся в копейках.
     */
    private function formatPriceInRubles(int $priceKopecks): string
    {
        return number_format($priceKopecks / 100, 2, '.', ' ') . ' ₽';
    }

    /**
     * Формирует объект политики бронирования:
     * условия отмены, время заезда/выезда, предоплата.
     *
     * @return array<string, mixed>
     */
    private function buildBookingPolicy(): array
    {
        return [
            'free_cancellation_hours' => self::FREE_CANCELLATION_HOURS,
            'free_cancellation_label' => sprintf(
                'Бесплатная отмена за %d ч. до заезда',
                self::FREE_CANCELLATION_HOURS
            ),
            'check_in_time'   => '14:00',
            'check_out_time'  => '12:00',
            'prepayment_required' => false,
        ];
    }
}
