<?php

declare(strict_types=1);

namespace Modules\Supermarket\Database\Factories;

use Modules\Supermarket\Infrastructure\Models\Certificate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'product_mark_id' => \Modules\Supermarket\Infrastructure\Models\ProductMark::factory(),
            'certificate_type' => fake()->randomElement(['veterinary', 'quality', 'origin']),
            'certificate_number' => fake()->bothify('CERT-##########'),
            'issue_date' => now()->subDays(fake()->numberBetween(1, 365)),
            'expiry_date' => now()->addDays(fake()->numberBetween(30, 365)),
            'issuer' => fake()->company(),
            'document_url' => fake()->url(),
            'status' => fake()->randomElement(['valid', 'expired', 'revoked']),
        ];
    }

    public function valid(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'valid',
            'expiry_date' => now()->addDays(fake()->numberBetween(30, 365)),
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expiry_date' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function revoked(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'revoked',
        ]);
    }

    public function veterinary(): self
    {
        return $this->state(fn (array $attributes) => [
            'certificate_type' => 'veterinary',
        ]);
    }
}
