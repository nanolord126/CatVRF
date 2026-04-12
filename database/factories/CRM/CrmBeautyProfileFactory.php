<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Domains\CRM\Models\CrmBeautyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика CrmBeautyProfile — beauty-профиль CRM-клиента.
 * Канон CatVRF 2026.
 */
final class CrmBeautyProfileFactory extends Factory
{
    protected $model = CrmBeautyProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'crm_client_id' => null,
            'tenant_id' => 1,
            'correlation_id' => $this->faker->uuid(),
            'skin_type' => $this->faker->randomElement(['dry', 'oily', 'combination', 'normal', 'sensitive']),
            'hair_type' => $this->faker->randomElement(['straight', 'wavy', 'curly', 'coily']),
            'hair_color' => $this->faker->randomElement(['blonde', 'brunette', 'red', 'black', 'grey']),
            'face_shape' => $this->faker->randomElement(['oval', 'round', 'square', 'heart', 'oblong']),
            'allergies' => $this->faker->randomElements(['latex', 'fragrance', 'paraben', 'sulfate'], 1),
            'contraindications' => [],
            'preferred_masters' => [],
            'preferred_services' => $this->faker->randomElements(['haircut', 'coloring', 'manicure', 'pedicure', 'facial'], 3),
            'favorite_products' => [],
            'before_after_photos' => [],
            'birthday' => $this->faker->optional(0.5)->date(),
            'special_dates' => [],
        ];
    }
}
