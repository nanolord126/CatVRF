<?php declare(strict_types=1);

namespace App\Domains\Taxi\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TaxiVehicleResource - Vehicle card with details, photo, class
 * Classic taxi-style vehicle profile like Yandex.Taxi
 */
final class TaxiVehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'plate_number' => $this->plate_number,
            'plate_number_formatted' => $this->formatPlateNumber($this->plate_number),
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => (int) $this->year,
            'color' => $this->color,
            'color_hex' => $this->color_hex,
            'vehicle_class' => $this->vehicle_class,
            'vehicle_class_name' => $this->getVehicleClassName($this->vehicle_class),
            'photo_url' => $this->photo_url,
            'photo_thumbnail_url' => $this->photo_thumbnail_url,
            'rating' => (float) $this->rating,
            'is_active' => (bool) $this->is_active,
            'is_insured' => (bool) $this->is_insured,
            'inspection_status' => $this->inspection_status,
            'inspection_valid_until' => $this->inspection_valid_until?->toIso8601String(),
            'insurance_valid_until' => $this->insurance_valid_until?->toIso8601String(),
            'capacity' => [
                'passengers' => (int) $this->passenger_capacity,
                'luggage' => (int) $this->luggage_capacity,
            ],
            'features' => [
                'air_conditioner' => (bool) $this->has_air_conditioner,
                'wifi' => (bool) $this->has_wifi,
                'usb_charger' => (bool) $this->has_usb_charger,
                'child_seat' => (bool) $this->has_child_seat,
                'booster_seat' => (bool) $this->has_booster_seat,
                'pet_friendly' => (bool) $this->is_pet_friendly,
                'smoking_allowed' => (bool) $this->smoking_allowed,
                'wheelchair_accessible' => (bool) $this->is_wheelchair_accessible,
            ],
            'documents' => [
                'pts_number' => $this->pts_number,
                'pts_valid_until' => $this->pts_valid_until?->toIso8601String(),
                'sts_number' => $this->sts_number,
                'sts_valid_until' => $this->sts_valid_until?->toIso8601String(),
                'insurance_policy_number' => $this->insurance_policy_number,
            ],
            'technical' => [
                'vin' => $this->vin,
                'engine_volume' => $this->engine_volume,
                'fuel_type' => $this->fuel_type,
                'transmission' => $this->transmission,
                'mileage' => (int) $this->mileage,
            ],
            'tariff_class' => $this->getTariffClass($this->vehicle_class),
            'base_tariff_price' => $this->getBaseTariffPrice($this->vehicle_class),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    private function formatPlateNumber(string $plate): string
    {
        return preg_replace('/([А-Я])(\d{3})([А-Я]{2})(\d{2,3})/iu', '$1 $2 $3 $4', $plate);
    }

    private function getVehicleClassName(string $class): string
    {
        return match($class) {
            'economy' => 'Эконом',
            'comfort' => 'Комфорт',
            'comfort_plus' => 'Комфорт+',
            'business' => 'Бизнес',
            'premium' => 'Премиум',
            'van' => 'Минивэн',
            'cargo' => 'Грузовой',
            default => 'Стандарт',
        };
    }

    private function getTariffClass(string $class): string
    {
        return match($class) {
            'economy' => 'econom',
            'comfort' => 'comfort',
            'comfort_plus' => 'comfortplus',
            'business' => 'business',
            'premium' => 'vip',
            'van' => 'minivan',
            'cargo' => 'cargo',
            default => 'standard',
        };
    }

    private function getBaseTariffPrice(string $class): int
    {
        return match($class) {
            'economy' => 15000,
            'comfort' => 20000,
            'comfort_plus' => 25000,
            'business' => 35000,
            'premium' => 50000,
            'van' => 30000,
            'cargo' => 40000,
            default => 15000,
        };
    }
}
