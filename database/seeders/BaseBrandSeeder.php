<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\{Brand, Category};
use Illuminate\Database\Seeder;

/**
 * Базовый seeder для брендов (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class BaseBrandSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AutoBrands::class,
            BeautyBrands::class,
            ClinicBrands::class,
            ConstructionBrands::class,
            EducationBrands::class,
            ElectronicsBrands::class,
            FoodBrands::class,
            HotelBrands::class,
            RetailBrands::class,
            SportBrands::class,
            VetBrands::class,
            OtherVerticalsBrands::class,
        ]);
    }

    protected function seedBrands(string $vertical, array $brands): void
    {
        $categories = Category::where('vertical', $vertical)->get();
        
        if ($categories->isEmpty()) {
            $categories = collect([
                Category::firstOrCreate(
                    ['slug' => \Illuminate\Support\Str::slug($vertical . '-general')],
                    ['name' => 'General', 'vertical' => $vertical, 'is_active' => true]
                )
            ]);
        }

        foreach ($brands as $data) {
            $brand = Brand::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'country' => $data['country'] ?? 'Global',
                    'description' => $data['description'] ?? null,
                    'is_platform_partner' => $data['is_platform_partner'] ?? false,
                ]
            );

            $brand->categories()->syncWithoutDetaching($categories->pluck('id'));
        }
    }

    protected function getSlug(string $vertical): string
    {
        return \Illuminate\Support\Str::slug($vertical . '-general');
    }
}


