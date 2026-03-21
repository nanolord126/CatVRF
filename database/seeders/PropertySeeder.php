<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Domains\RealEstate\Property;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Тестовые недвижимости (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $properties = [
            ["name" => "Modern Apartment Downtown", "type" => "apartment", "area" => 75, "price" => 150000],
            ["name" => "Beach House", "type" => "rental", "area" => 120, "price" => 250000],
            ["name" => "Studio Loft", "type" => "apartment", "area" => 50, "price" => 100000],
        ];

        foreach ($properties as $property) {
            Property::factory()->create(array_merge($property, [
                "correlation_id" => (string) Str::uuid()
            ]));
        }
    }
}

