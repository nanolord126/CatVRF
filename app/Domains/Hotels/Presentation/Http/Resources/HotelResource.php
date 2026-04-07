<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Presentation\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * HotelResource — API Resource для представления данных отеля (B2C).
 *
 * Форматирует доменную сущность Hotel для JSON-ответа.
 * Включает адрес, удобства, рейтинг, вложенные номера (если загружены).
 * Отображает количество доступных номеров и минимальную цену за ночь.
 *
 * @package App\Domains\Hotels\Presentation\Http\Resources
 */
final class HotelResource extends JsonResource
{
    /**
     * Преобразует Hotel-сущность в массив для JSON-ответа.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->getId()->toString(),
            'name'                => $this->getName(),
            'description'         => $this->getDescription(),
            'address'             => $this->buildAddressArray(),
            'amenities'           => $this->getAmenities()->toArray(),
            'rating'              => $this->getRating(),
            'rating_stars'        => $this->formatRatingStars($this->getRating()),
            'available_rooms_count' => $this->when(
                $this->hasAvailableRoomsCount(),
                fn(): int => $this->getAvailableRoomsCount()
            ),
            'min_price_per_night' => $this->when(
                $this->hasMinPrice(),
                fn(): int => $this->getMinPricePerNight()
            ),
            'rooms'               => RoomResource::collection($this->whenLoaded('rooms')),
            'created_at'          => $this->getCreatedAt()?->toIso8601String(),
        ];
    }

    /**
     * Дополнительные мета-данные, обертывающие коллекцию.
     *
     * @return array<string, mixed>
     */
    public static function collection(mixed $resource): AnonymousResourceCollection
    {
        return parent::collection($resource);
    }

    /**
     * Добавляет мета-данные к ответу на уровне коллекции.
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
     * Формирует структуру адреса для ответа.
     *
     * @return array<string, string|null>
     */
    private function buildAddressArray(): array
    {
        $address = $this->getAddress();

        return [
            'country'      => $address->getCountry(),
            'city'         => $address->getCity(),
            'street'       => $address->getStreet(),
            'house_number' => $address->getHouseNumber(),
            'zip_code'     => $address->getZipCode(),
            'full'         => implode(', ', array_filter([
                $address->getCountry(),
                $address->getCity(),
                $address->getStreet(),
                $address->getHouseNumber(),
            ])),
        ];
    }

    /**
     * Преобразует рейтинг в звёздочное отображение (от 1 до 5).
     */
    private function formatRatingStars(float $rating): string
    {
        $full  = (int) floor($rating);
        $empty = 5 - $full;

        return str_repeat('★', $full) . str_repeat('☆', $empty);
    }

    /**
     * Проверяет наличие счётчика доступных номеров в сущности.
     */
    private function hasAvailableRoomsCount(): bool
    {
        return method_exists($this->resource, 'getAvailableRoomsCount');
    }

    /**
     * Проверяет наличие цены в сущности.
     */
    private function hasMinPrice(): bool
    {
        return method_exists($this->resource, 'getMinPricePerNight');
    }
}
