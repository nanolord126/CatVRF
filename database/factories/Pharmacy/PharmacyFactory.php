<?php declare(strict_types=1);

namespace Database\Factories\Pharmacy;

use App\Domains\Pharmacy\Models\Pharmacy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class PharmacyFactory extends Factory
{
    protected $model = Pharmacy::class;
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => 'test-tenant',
            'name' => $this->faker->company,
            'address' => $this->faker->address,
            'correlation_id' => (string) Str::uuid(),
        ];
    }
}
