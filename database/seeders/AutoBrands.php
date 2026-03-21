<?php

declare(strict_types=1);

namespace Database\Seeders;

/**
 * Бренды автомобилей (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class AutoBrands extends BaseBrandSeeder
{
    public function run(): void
    {
        $this->seedBrands('AutoService', [
            ['name' => 'Toyota', 'country' => 'Japan'], ['name' => 'Tesla', 'country' => 'USA'],
            ['name' => 'BMW', 'country' => 'Germany'], ['name' => 'Mercedes-Benz', 'country' => 'Germany'],
            ['name' => 'Volkswagen', 'country' => 'Germany'], ['name' => 'Honda', 'country' => 'Japan'],
            ['name' => 'Ford', 'country' => 'USA'], ['name' => 'Hyundai', 'country' => 'South Korea'],
            ['name' => 'Kia', 'country' => 'South Korea'], ['name' => 'Nissan', 'country' => 'Japan'],
            ['name' => 'Audi', 'country' => 'Germany'], ['name' => 'Porsche', 'country' => 'Germany'],
            ['name' => 'Volvo', 'country' => 'Sweden'], ['name' => 'Mazda', 'country' => 'Japan'],
            ['name' => 'Chevrolet', 'country' => 'USA'], ['name' => 'Lexus', 'country' => 'Japan'],
            ['name' => 'Land Rover', 'country' => 'UK'], ['name' => 'Jaguar', 'country' => 'UK'],
            ['name' => 'Ferrari', 'country' => 'Italy'], ['name' => 'Lamborghini', 'country' => 'Italy'],
            ['name' => 'Bentley', 'country' => 'UK'], ['name' => 'Rolls-Royce', 'country' => 'UK'],
            ['name' => 'BYD', 'country' => 'China'], ['name' => 'Zeekr', 'country' => 'China'],
            ['name' => 'Li Auto', 'country' => 'China'], ['name' => 'Nio', 'country' => 'China'],
            ['name' => 'Geely', 'country' => 'China'], ['name' => 'Chery', 'country' => 'China'],
            ['name' => 'Haval', 'country' => 'China'], ['name' => 'Changan', 'country' => 'China'],
            ['name' => 'Rivian', 'country' => 'USA'], ['name' => 'Lucid', 'country' => 'USA'],
            ['name' => 'Subaru', 'country' => 'Japan'], ['name' => 'Mitsubishi', 'country' => 'Japan'],
            ['name' => 'Skoda', 'country' => 'Czech Republic'], ['name' => 'Peugeot', 'country' => 'France'],
            ['name' => 'Renault', 'country' => 'France'], ['name' => 'Fiat', 'country' => 'Italy'],
            ['name' => 'Alfa Romeo', 'country' => 'Italy'], ['name' => 'Jeep', 'country' => 'USA'],
            ['name' => 'Dodge', 'country' => 'USA'], ['name' => 'RAM', 'country' => 'USA'],
            ['name' => 'Cadillac', 'country' => 'USA'], ['name' => 'GMC', 'country' => 'USA'],
            ['name' => 'Isuzu', 'country' => 'Japan'], ['name' => 'Suzuki', 'country' => 'Japan'],
            ['name' => 'Genesis', 'country' => 'South Korea'], ['name' => 'Polestar', 'country' => 'Sweden'],
            ['name' => 'Lotus', 'country' => 'UK'],
            ['name' => 'Kot-Drive (Partner)', 'country' => 'Russia', 'is_platform_partner' => true],
        ]);
    }
}


