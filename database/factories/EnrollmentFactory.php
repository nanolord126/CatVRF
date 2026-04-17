<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Education\Models\Enrollment;
use App\Domains\Education\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

final class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 1,
            'user_id' => \App\Models\User::factory(),
            'course_id' => Course::factory(),
            'corporate_contract_id' => null,
            'mode' => $this->faker->randomElement(['b2c', 'b2b']),
            'ai_path' => null,
            'completed_at' => null,
            'progress_percent' => 0,
            'correlation_id' => \Illuminate\Support\Str::uuid(),
        ];
    }

    public function completed(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'completed_at' => now()->subDays(rand(1, 30)),
                'progress_percent' => 100,
            ];
        });
    }

    public function inProgress(int $progress = 50): self
    {
        return $this->state(function (array $attributes) use ($progress) {
            return [
                'progress_percent' => $progress,
            ];
        });
    }

    public function b2b(int $contractId = 1): self
    {
        return $this->state(function (array $attributes) use ($contractId) {
            return [
                'corporate_contract_id' => $contractId,
                'mode' => 'b2b',
            ];
        });
    }

    public function withAiPath(array $aiPath): self
    {
        return $this->state(function (array $attributes) use ($aiPath) {
            return [
                'ai_path' => $aiPath,
            ];
        });
    }
}
