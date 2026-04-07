<?php

declare(strict_types=1);

namespace Database\Factories\Domains\RealEstate;

use App\Domains\RealEstate\Enums\PropertyStatus;
use App\Domains\RealEstate\Enums\PropertyType;
use App\Domains\RealEstate\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\RealEstate\Models\Property>
 */
final class PropertyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Property::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(PropertyType::cases());
        $isResidential = in_array($type, [PropertyType::APARTMENT, PropertyType::HOUSE]);

        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => fn () => Tenant::factory()->create()->id,
            'business_group_id' => null,
            'agent_id' => fn () => User::factory()->create()->id,
            'correlation_id' => Str::uuid()->toString(),
            'type' => $type,
            'status' => $this->faker->randomElement(PropertyStatus::cases()),
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->realText(500),
            'address' => $this->faker->address(),
            'location' => [
                'lat' => $this->faker->latitude(55.5, 55.9),
                'lon' => $this->faker->longitude(37.3, 37.9),
            ],
            'price' => $this->faker->numberBetween(1000000, 50000000),
            'area' => $this->faker->numberBetween(20, 500),
            'rooms' => $isResidential ? $this->faker->numberBetween(1, 5) : null,
            'floor' => $isResidential ? $this->faker->numberBetween(1, 25) : null,
            'total_floors' => $isResidential ? $this->faker->numberBetween(5, 25) : null,
            'photos' => [
                ['path' => 'photos/realestate/sample1.jpg', 'caption' => 'Гостиная'],
                ['path' => 'photos/realestate/sample2.jpg', 'caption' => 'Кухня'],
                ['path' => 'photos/realestate/sample3.jpg', 'caption' => 'Спальня'],
            ],
            'documents' => [
                ['path' => 'documents/realestate/plan.pdf', 'title' => 'План квартиры'],
                ['path' => 'documents/realestate/egrn.pdf', 'title' => 'Выписка из ЕГРН'],
            ],
            'amenities' => $this->faker->randomElements(
                ['wifi', 'parking', 'ac', 'heating', 'kitchen', 'tv', 'balcony', 'elevator'],
                $this->faker->numberBetween(2, 6)
            ),
            'tags' => $this->faker->randomElements(['срочно', 'торг', 'эксклюзив', 'новостройка'], 2),
            'meta' => [
                'source' => 'factory',
                'version' => '1.0',
            ],
        ];
    }

    /**
     * Indicate that the property is active.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function active(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PropertyStatus::ACTIVE,
            ];
        });
    }

    /**
     * Indicate that the property is an apartment.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function apartment(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => PropertyType::APARTMENT,
                'rooms' => $this->faker->numberBetween(1, 4),
                'floor' => $this->faker->numberBetween(2, 15),
                'total_floors' => $this->faker->numberBetween(9, 25),
            ];
        });
    }

    /**
     * Indicate that the property is a house.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function house(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => PropertyType::HOUSE,
                'rooms' => $this->faker->numberBetween(3, 8),
                'floor' => null,
                'total_floors' => $this->faker->numberBetween(1, 3),
                'area' => $this->faker->numberBetween(100, 1000),
            ];
        });
    }
}
