<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Domains\CRM\Models\CrmAutoProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика CrmAutoProfile — авто-профиль CRM-клиента.
 * Канон CatVRF 2026.
 */
final class CrmAutoProfileFactory extends Factory
{
    protected $model = CrmAutoProfile::class;

    private const BRANDS = ['BMW', 'Audi', 'Mercedes', 'Toyota', 'Hyundai', 'Kia', 'Lada', 'Volkswagen'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'crm_client_id' => null,
            'tenant_id' => 1,
            'correlation_id' => $this->faker->uuid(),
            'vin' => strtoupper($this->faker->bothify('WBA#####??#######')),
            'car_brand' => $this->faker->randomElement(self::BRANDS),
            'car_model' => $this->faker->word(),
            'car_year' => $this->faker->numberBetween(2010, 2025),
            'car_color' => $this->faker->safeColorName(),
            'mileage_km' => $this->faker->numberBetween(0, 300000),
            'engine_type' => $this->faker->randomElement(['petrol', 'diesel', 'electric', 'hybrid']),
            'transmission' => $this->faker->randomElement(['automatic', 'manual', 'cvt', 'robot']),
            'insurance_expires_at' => $this->faker->optional(0.7)->dateTimeBetween('now', '+12 months'),
            'next_service_at' => $this->faker->optional(0.5)->dateTimeBetween('now', '+6 months'),
            'service_history' => [],
            'preferred_parts_brands' => $this->faker->randomElements(['Bosch', 'NGK', 'Mann', 'Denso'], 2),
            'car_preferences' => [],
            'drivers_license_category' => $this->faker->randomElement(['B', 'B1', 'C', 'D']),
            'has_garage' => $this->faker->boolean(40),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }
}
