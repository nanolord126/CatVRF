<?php declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class MeatShopsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = DB::table("tenants")->value("id");
        if (!$tenantId) {
            $tenantId = 1;
        }

        for ($i = 0; $i < 5; $i++) {
            $productId = DB::table("meat_shops")->insertGetId([
                "tenant_id" => $tenantId,
                "name" => "Steak " . $i,
                "sku" => "STK-" . Str::random(8),
                "meat_type" => "beef",
                "cut" => "steak",
                "uuid" => Str::uuid()->toString(),
                "weight_g" => 500,
                "price" => 1500,
                "correlation_id" => Str::uuid()->toString(),
                "created_at" => now(),
                "updated_at" => now()
            ]);

            DB::table("meat_orders")->insert([
                "tenant_id" => $tenantId,
                "client_id" => 1,
                "items" => json_encode([["product_id" => $productId, "qty" => 1]]),
                "total_amount" => 1500 + $i * 100,
                "delivery_address" => "Test Address",
                "status" => "pending",
                "created_at" => now(),
                "updated_at" => now(),
                "correlation_id" => Str::uuid()->toString(),
            ]);
        }
    }
}

