<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Books\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

final class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'uuid' => $this->faker->uuid(),
            'correlation_id' => $this->faker->uuid(),
            'title' => $this->faker->sentence(3),
            'author' => $this->faker->name(),
            'isbn' => $this->faker->isbn13(),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->numberBetween(30000, 100000),
            'category' => $this->faker->word(),
            'rating' => $this->faker->randomFloat(1, 1, 5),
            'tags' => json_encode(['book', 'reading']),
        ];
    }
}
