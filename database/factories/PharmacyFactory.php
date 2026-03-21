<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Pharmacy\Models\Pharmacy;
use Illuminate\Database\Eloquent\Factories\Factory;

final class PharmacyFactory extends Factory
{
    protected $model = Pharmacy::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'correlation_id' => $this->faker->uuid(),
            'name' => $this->faker->randomElement([
                'Парацетамол 500мг',
                'Ибупрофен 400мг',
                'Аспирин кардио',
                'Витамин C комплекс',
                'Антибиотик Амоксициллин',
                'Капли глазные Визин',
                'Сироп от кашля',
                'Мазь антисептическая',
            ]),
            'sku' => 'PHARM-' . strtoupper($this->faker->lexify('????')),
            'mnn' => $this->faker->randomElement(['paracetamol', 'ibuprofen', 'acetylsalicylic_acid', 'amoxicillin']),
            'form' => $this->faker->randomElement(['tablet', 'capsule', 'syrup', 'drops', 'ointment', 'injection']),
            'dosage' => $this->faker->randomElement(['200mg', '400mg', '500mg', '1000mg', '5%', '10%']),
            'price' => $this->faker->numberBetween(50000, 200000),
            'current_stock' => $this->faker->numberBetween(20, 200),
            'is_otc' => $this->faker->boolean(70),
            'requires_prescription' => $this->faker->boolean(30),
            'rating' => $this->faker->randomFloat(1, 4.0, 5.0),
            'tags' => null,
        ];
    }

    public function prescription(): self
    {
        return $this->state(fn (array $attributes) => [
            'requires_prescription' => true,
            'is_otc' => false,
            'price' => $this->faker->numberBetween(100000, 200000),
        ]);
    }

    public function otc(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_otc' => true,
            'requires_prescription' => false,
            'price' => $this->faker->numberBetween(50000, 150000),
        ]);
    }
}
