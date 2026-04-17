<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Education\Models\Slot;
use Illuminate\Database\Eloquent\Factories\Factory;

final class SlotFactory extends Factory
{
    protected $model = Slot::class;

    public function definition(): array
    {
        $startTime = now()->addDays(rand(1, 7))->setHour(rand(9, 18))->setMinute(0);
        $duration = rand(30, 120);

        return [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 1,
            'business_group_id' => null,
            'teacher_id' => null,
            'course_id' => null,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'start_time' => $startTime,
            'end_time' => $startTime->addMinutes($duration),
            'duration_minutes' => $duration,
            'capacity' => rand(5, 30),
            'booked_count' => 0,
            'slot_type' => $this->faker->randomElement(['webinar', 'tutoring', 'exam', 'consultation']),
            'status' => 'available',
            'meeting_link' => $this->faker->url(),
            'meeting_password' => $this->faker->password(8, 12),
            'metadata' => null,
            'correlation_id' => \Illuminate\Support\Str::uuid(),
        ];
    }

    public function webinar(): self
    {
        return $this->state(fn (array $attributes) => [
            'slot_type' => 'webinar',
            'capacity' => rand(20, 100),
        ]);
    }

    public function tutoring(): self
    {
        return $this->state(fn (array $attributes) => [
            'slot_type' => 'tutoring',
            'capacity' => 1,
        ]);
    }

    public function held(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'held',
            'booked_count' => 1,
        ]);
    }

    public function booked(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'booked',
            'booked_count' => rand(1, 5),
        ]);
    }
}
