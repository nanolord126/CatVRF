<?php

declare(strict_types=1);

namespace Database\Factories\Beauty;

use App\Domains\Beauty\Models\BeautyService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BeautyService>
 */
final class BeautyServiceFactory extends Factory
{
    protected $model = BeautyService::class;

    public function definition(): array
    {
        $serviceNames = [
            'Женская стрижка',
            'Мужская стрижка',
            'Окрашивание волос',
            'Маникюр классический',
            'Педикюр',
            'Массаж лица',
            'Массаж тела (60 мин)',
            'Макияж вечерний',
            'Наращивание ресниц',
            'Чистка лица',
        ];

        return [
            'tenant_id' => 1,
            'business_group_id' => null,
            'salon_id' => null,
            'master_id' => null,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'name' => $this->faker->randomElement($serviceNames),
            'description' => $this->faker->sentence(10),
            'duration_minutes' => $this->faker->randomElement([30, 45, 60, 90, 120]),
            'price' => $this->faker->numberBetween(100000, 500000), // 1000-5000 руб в копейках
            'consumables_json' => [
                ['name' => 'Перчатки', 'quantity' => 2],
                ['name' => 'Краска для волос', 'quantity' => 1],
            ],
            'is_active' => $this->faker->boolean(95),
            'tags' => [
                $this->faker->randomElement(['popular', 'new', 'premium', 'express']),
            ],
        ];
    }
}
