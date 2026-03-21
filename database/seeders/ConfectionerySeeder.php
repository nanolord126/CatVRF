<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Confectionery\Models\ConfectioneryShop;
use App\Domains\Confectionery\Models\Cake;
use App\Domains\Confectionery\Models\BakeryOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class ConfectionerySeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = DB::table("tenants")->value("id");
        if (!$tenantId) {
            $tenantId = 1;
        }

        for ($i = 0; $i < 5; $i++) {
            $productId = DB::table("confectionery_products")->insertGetId([
                "tenant_id" => $tenantId,
                "name" => "Cake " . $i,
                "type" => "cake",
                "price" => 1500 + $i * 100,
                "correlation_id" => Str::uuid()->toString(),
                "created_at" => now(),
                "updated_at" => now()
            ]);

            DB::table("bakery_orders")->insert([
                "tenant_id" => $tenantId,
                "client_id" => 1,
                "product_id" => $productId,
                "quantity" => 1,
                "total_amount" => 1500 + $i * 100,
                "status" => "pending",
                "created_at" => now(),
                "updated_at" => now(),
                "correlation_id" => Str::uuid()->toString(),
            ]);
        }
    }
}

