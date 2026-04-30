<?php

declare(strict_types=1);

namespace Modules\Supermarket\Database\Factories;

use Modules\Supermarket\Domain\Enums\ReturnReason;
use Modules\Supermarket\Domain\Enums\ReturnStatus;
use Modules\Supermarket\Infrastructure\Models\Return as ReturnModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReturnFactory extends Factory
{
    protected $model = ReturnModel::class;

    public function definition(): array
    {
        return [
            'order_id' => \App\Models\Order::factory(),
            'buyer_id' => \App\Models\User::factory(),
            'seller_id' => \App\Models\Tenant::factory(),
            'status' => ReturnStatus::PENDING->value,
            'reason_type' => fake()->randomElement(ReturnReason::cases())->value,
            'reason_comment' => fake()->sentence(),
            'total_amount' => fake()->randomFloat(2, 100, 5000) * 100, // в копейках
            'refund_amount' => fake()->randomFloat(2, 100, 5000) * 100,
            'is_cold_chain' => fake()->boolean(30), // 30% cold chain
            'return_method' => fake()->randomElement(['pickup', 'courier', 'self_delivery']),
            'images' => fake()->randomElements(['image1.jpg', 'image2.jpg', 'image3.jpg'], fake()->numberBetween(0, 3)),
            'approved_at' => null,
            'completed_at' => null,
            'rejected_at' => null,
            'reject_reason' => null,
            'auto_approved' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReturnStatus::PENDING->value,
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReturnStatus::APPROVED->value,
            'approved_at' => now()->subHours(fake()->numberBetween(1, 24)),
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReturnStatus::REJECTED->value,
            'rejected_at' => now()->subHours(fake()->numberBetween(1, 24)),
            'reject_reason' => fake()->sentence(),
            'refund_amount' => 0,
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReturnStatus::COMPLETED->value,
            'approved_at' => now()->subDays(fake()->numberBetween(1, 3)),
            'completed_at' => now()->subHours(fake()->numberBetween(1, 24)),
        ]);
    }

    public function coldChain(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_cold_chain' => true,
            'reason_type' => ReturnReason::SPOILED->value,
        ]);
    }

    public function withImages(): self
    {
        return $this->state(fn (array $attributes) => [
            'images' => ['image1.jpg', 'image2.jpg'],
        ]);
    }

    public function autoApproved(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReturnStatus::APPROVED->value,
            'auto_approved' => true,
            'approved_at' => now()->subMinutes(fake()->numberBetween(5, 60)),
        ]);
    }
}
