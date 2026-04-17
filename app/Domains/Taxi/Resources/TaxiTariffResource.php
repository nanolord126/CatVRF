<?php declare(strict_types=1);

namespace App\Domains\Taxi\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TaxiTariffResource - Pricing tiers and tariffs
 * Classic taxi-style tariff structure like Yandex.Taxi
 */
final class TaxiTariffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'vehicle_class' => $this->vehicle_class,
            'vehicle_class_name' => $this->getVehicleClassName($this->vehicle_class),
            'icon' => $this->icon,
            'color' => $this->color,
            'is_active' => (bool) $this->is_active,
            'is_available_now' => (bool) $this->is_available_now,
            'pricing' => [
                'base_price' => (int) $this->base_price,
                'price_per_km' => (int) $this->price_per_km,
                'price_per_minute' => (int) $this->price_per_minute,
                'minimum_price' => (int) $this->minimum_price,
                'waiting_price_per_minute' => (int) $this->waiting_price_per_minute,
                'currency' => 'RUB',
            ],
            'surge' => [
                'current_multiplier' => (float) $this->current_surge_multiplier,
                'max_multiplier' => (float) $this->max_surge_multiplier,
                'is_surge_active' => $this->current_surge_multiplier > 1.0,
            ],
            'features' => [
                'fixed_price_available' => (bool) $this->fixed_price_available,
                'preorder_available' => (bool) $this->preorder_available,
                'split_payment_available' => (bool) $this->split_payment_available,
                'corporate_payment_available' => (bool) $this->corporate_payment_available,
                'voice_order_available' => (bool) $this->voice_order_available,
            ],
            'vehicle_requirements' => [
                'min_year' => (int) $this->min_vehicle_year,
                'min_rating' => (float) $this->min_vehicle_rating,
                'required_features' => $this->required_features ?? [],
                'passenger_capacity' => (int) $this->passenger_capacity,
                'luggage_capacity' => (int) $this->luggage_capacity,
            ],
            'estimated_time' => [
                'average_wait_time_minutes' => (int) $this->average_wait_time_minutes,
                'max_wait_time_minutes' => (int) $this->max_wait_time_minutes,
            ],
            'availability' => [
                'available_drivers_count' => (int) $this->available_drivers_count,
                'is_available' => $this->available_drivers_count > 0,
            ],
            'b2b_pricing' => [
                'enabled' => (bool) $this->b2b_enabled,
                'discount_percentage' => (float) $this->b2b_discount_percentage,
                'monthly_limit' => (int) $this->b2b_monthly_limit,
            ],
            'promotions' => [
                'current_promo' => $this->current_promo_code,
                'discount_amount' => $this->current_promo_discount !== null ? (int) $this->current_promo_discount : null,
                'promo_valid_until' => $this->current_promo_valid_until?->toIso8601String(),
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
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
}
