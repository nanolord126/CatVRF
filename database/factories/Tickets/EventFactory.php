<?php

declare(strict_types=1);

namespace Database\Factories\Tickets;

use App\Domains\Tickets\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

final class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'slug' => $this->faker->slug(),
            'description' => $this->faker->paragraphs(3, true),
            'category' => $this->faker->randomElement(['music', 'theater', 'cinema', 'sports', 'conference', 'festival', 'workshop']),
            'location' => $this->faker->city(),
            'address' => $this->faker->address(),
            'organizer_name' => $this->faker->company(),
            'organizer_phone' => $this->faker->phoneNumber(),
            'organizer_email' => $this->faker->companyEmail(),
            'start_datetime' => now()->addDays($this->faker->numberBetween(1, 60)),
            'end_datetime' => now()->addDays($this->faker->numberBetween(1, 60))->addHours(3),
            'total_capacity' => $this->faker->numberBetween(100, 5000),
            'sold_count' => 0,
            'min_ticket_price' => $this->faker->numberBetween(50000, 500000),
            'rating' => $this->faker->randomFloat(1, 0, 5),
            'review_count' => $this->faker->numberBetween(0, 500),
            'is_online' => false,
            'require_age_check' => false,
            'min_age' => 0,
            'status' => 'published',
            'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            'tags' => ['event', $this->faker->word()],
            'meta' => [],
        ];
    }

    public function music(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'music',
                'name' => $this->faker->sentence(2) . ' Live',
                'total_capacity' => $this->faker->numberBetween(300, 3000),
                'min_ticket_price' => $this->faker->numberBetween(100000, 800000),
            ];
        });
    }

    public function sports(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'sports',
                'name' => $this->faker->sentence(3) . ' Championship',
                'total_capacity' => $this->faker->numberBetween(1000, 5000),
                'min_ticket_price' => $this->faker->numberBetween(50000, 300000),
            ];
        });
    }

    public function ageRestricted(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'require_age_check' => true,
                'min_age' => 18,
            ];
        });
    }
}
