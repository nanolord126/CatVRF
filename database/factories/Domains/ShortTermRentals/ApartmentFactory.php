<?php declare(strict_types=1);

namespace Database\Factories\Domains\ShortTermRentals;

use App\Domains\ShortTermRentals\Models\Apartment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class ApartmentFactory extends Factory
{
    protected $model = Apartment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'owner_id' => 1,
            'uuid' => Str::uuid(),
            'name' => fake()->words(3, true),
            'address' => fake()->address(),
            'rooms' => fake()->numberBetween(1, 4),
            'area_sqm' => fake()->randomFloat(1, 30, 120),
            'floor' => fake()->numberBetween(1, 15),
            'amenities' => ['wifi', 'kitchen', 'washing_machine'],
            'images' => [fake()->imageUrl()],
            'price_per_night' => fake()->randomFloat(2, 1000, 10000),
            'available_dates' => [],
            'deposit_amount' => fake()->randomFloat(2, 3000, 10000),
            'is_active' => true,
            'correlation_id' => Str::uuid(),
            'tags' => ['city_center', 'quiet'],
        ];
    }
}
