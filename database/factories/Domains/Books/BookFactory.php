<?php declare(strict_types=1);

namespace Database\Factories\Domains\Books;

use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => $this->faker->uuid(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'isbn' => $this->faker->unique()->isbn13(),
            'author' => $this->faker->name(),
            'price' => $this->faker->numberBetween(1000, 50000),
            'stock' => $this->faker->numberBetween(0, 100),
            'genre' => $this->faker->randomElement(['Fiction', 'Non-Fiction', 'Mystery', 'Romance', 'Science']),
            'rating' => $this->faker->randomFloat(2, 1, 5),
            'tags' => json_encode(['book' => true]),
            'correlation_id' => $this->faker->uuid(),
        ];
    }
}
