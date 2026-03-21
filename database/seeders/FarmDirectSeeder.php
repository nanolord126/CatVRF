<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\FarmDirect\Models\FarmOrder;
use App\Domains\FarmDirect\Models\Farm;
use App\Domains\FarmDirect\Models\FarmProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class FarmDirectSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        $farms = Farm::factory()
            ->count(3)
            ->create([
                "tenant_id" => $tenantId,
                "correlation_id" => Str::uuid()->toString(),
            ]);

        foreach ($farms as $farm) {
            FarmProduct::factory()
                ->count(3)
                ->create([
                    "farm_id" => $farm->id,
                    "tenant_id" => $tenantId,
                    "correlation_id" => Str::uuid()->toString(),
                    "tags" => json_encode(["seeder" => true], JSON_UNESCAPED_UNICODE),
                ]);
        }

        $products = FarmProduct::where("tenant_id", $tenantId)->get();

        foreach ($farms as $farm) {
            FarmOrder::factory()
                ->count(2)
                ->create([
                    "farm_id" => $farm->id,
                    "tenant_id" => $tenantId,
                    "client_id" => 1,
                    "items" => json_encode([["product_id" => $products->first()->id, "qty" => 1]]),
                    "correlation_id" => Str::uuid()->toString(),
                    "status" => collect(["pending", "delivered", "cancelled"])->random(),
                    "tags" => json_encode(["seeder" => true], JSON_UNESCAPED_UNICODE),
                ]);
        }
    }
}

