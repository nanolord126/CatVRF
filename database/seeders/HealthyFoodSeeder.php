<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\HealthyFood\Models\HealthyFood;
use Illuminate\Database\Seeder;

final class HealthyFoodSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = \Illuminate\Support\Facades\DB::table("tenants")->value("id");
        if (!$tenantId) {
            $tenantId = 1;
        }

        $items = [
            ["name" => "Салат Цезарь органик", "sku" => "HF-CSR001", "diet_type" => "balanced", "calories" => 450, "protein_g" => 25, "carbs_g" => 35, "fat_g" => 20, "price" => 180000],
            ["name" => "Обед Кето боул", "sku" => "HF-KET002", "diet_type" => "keto", "calories" => 550, "protein_g" => 40, "carbs_g" => 15, "fat_g" => 35, "price" => 220000],
            ["name" => "Каша гречка овощи", "sku" => "HF-OAT003", "diet_type" => "balanced", "calories" => 380, "protein_g" => 18, "carbs_g" => 55, "fat_g" => 12, "price" => 150000],
        ];

        foreach ($items as $item) {
            HealthyFood::updateOrCreate(
                ["sku" => $item["sku"], "tenant_id" => $tenantId],
                array_merge($item, [
                    "uuid" => \Illuminate\Support\Str::uuid(),
                    "tenant_id" => $tenantId,
                    "current_stock" => random_int(5, 100),
                    "rating" => random_int(40, 50) / 10,
                ])
            );
        }
    }
}

