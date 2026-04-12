<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Domains\CRM\Models\CrmHotelGuestProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика CrmHotelGuestProfile — hotel-профиль CRM-клиента.
 * Канон CatVRF 2026.
 */
final class CrmHotelGuestProfileFactory extends Factory
{
    protected $model = CrmHotelGuestProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'crm_client_id' => null,
            'correlation_id' => $this->faker->uuid(),
            'preferred_room_type' => $this->faker->randomElement(['standard', 'superior', 'deluxe', 'suite', 'presidential']),
            'preferred_floor' => $this->faker->randomElement(['low', 'mid', 'high', 'any']),
            'preferred_view' => $this->faker->randomElement(['city', 'sea', 'garden', 'mountain', 'any']),
            'preferred_amenities' => $this->faker->randomElements(['minibar', 'bathrobe', 'spa', 'gym', 'pool'], 3),
            'is_smoking' => $this->faker->boolean(15),
            'has_pets' => $this->faker->boolean(10),
            'is_vip_service' => $this->faker->boolean(20),
            'dietary_restrictions' => $this->faker->randomElements(['vegetarian', 'halal', 'kosher', 'gluten_free'], 1),
            'allergies' => [],
            'preferred_language' => $this->faker->randomElement(['ru', 'en', 'de', 'fr', 'zh']),
            'passport_country' => $this->faker->countryCode(),
            'frequent_guest_number' => $this->faker->optional(0.3)->numerify('FG-######'),
            'birthday' => $this->faker->optional(0.5)->date(),
            'special_dates' => [],
            'average_review_rating' => $this->faker->randomFloat(2, 3.0, 5.0),
            'total_stays' => $this->faker->numberBetween(0, 50),
            'total_nights' => $this->faker->numberBetween(0, 200),
        ];
    }
}
