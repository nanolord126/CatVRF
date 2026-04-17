<?php declare(strict_types=1);

namespace Database\Factories\Beauty;

use App\Domains\Beauty\Models\Salon;
use Illuminate\Database\Eloquent\Factories\Factory;

final class SalonFactory extends Factory
{
    protected $model = Salon::class;

    public function definition(): array
    {
        $salonNames = [
            'Эстетика Премиум', 'Beauty Studio Luxe', 'Салон Красоты Модерн',
            'Glamour House', 'Natural Beauty', 'Стиль и Шарм',
            'Ваш Образ', 'Перфект Лук', 'Бьюти Лаб',
        ];

        $cities = [
            ['name' => 'Москва', 'lat' => 55.7558, 'lon' => 37.6173],
            ['name' => 'Санкт-Петербург', 'lat' => 59.9343, 'lon' => 30.3351],
            ['name' => 'Казань', 'lat' => 55.7887, 'lon' => 49.1221],
            ['name' => 'Новосибирск', 'lat' => 55.0084, 'lon' => 82.9357],
            ['name' => 'Екатеринбург', 'lat' => 56.8389, 'lon' => 60.6057],
        ];

        $city = $this->faker->randomElement($cities);
        $street = $this->faker->streetName();
        $building = $this->faker->buildingNumber();

        return [
            'tenant_id' => 1,
            'business_group_id' => null,
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            'name' => $this->faker->randomElement($salonNames) . ' ' . $this->faker->randomElement(['I', 'II', 'III', '']),
            'address' => "{$city['name']}, {$street}, {$building}",
            'lat' => $city['lat'] + $this->faker->randomFloat(-4, 4, 6),
            'lon' => $city['lon'] + $this->faker->randomFloat(-4, 4, 6),
            'status' => 'active',
            'tags' => json_encode($this->faker->randomElements(['премиум', 'стрижка', 'маникюр', 'макияж', 'окрашивание', 'уход', 'спа'], 3), JSON_THROW_ON_ERROR),
            'metadata' => json_encode([
                'rating' => $this->faker->randomFloat(1, 3.5, 5.0),
                'review_count' => $this->faker->numberBetween(50, 500),
                'phone' => $this->faker->phoneNumber(),
                'website' => $this->faker->url(),
                'working_hours' => [
                    'monday' => ['09:00', '21:00'],
                    'tuesday' => ['09:00', '21:00'],
                    'wednesday' => ['09:00', '21:00'],
                    'thursday' => ['09:00', '21:00'],
                    'friday' => ['09:00', '21:00'],
                    'saturday' => ['10:00', '20:00'],
                    'sunday' => ['10:00', '19:00'],
                ],
            ], JSON_THROW_ON_ERROR),
            'is_active' => true,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'is_active' => false,
        ]);
    }

    public function premium(): self
    {
        return $this->state(fn (array $attributes) => [
            'tags' => json_encode(['премиум', 'лакшери', 'vip'], JSON_THROW_ON_ERROR),
            'metadata' => json_encode(array_merge(json_decode($attributes['metadata'], true), [
                'rating' => $this->faker->randomFloat(1, 4.5, 5.0),
                'premium_features' => true,
            ]), JSON_THROW_ON_ERROR),
        ]);
    }
}
