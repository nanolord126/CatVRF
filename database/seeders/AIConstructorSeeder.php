<?php

namespace Database\Seeders;

use App\Models\AI\InteriorDesignSession;
use App\Models\AI\BeautyTryOnSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AIConstructorSeeder extends Seeder
{
    /**
     * Предустановленные данные для демонстрации AI-функционала.
     */
    public function run(): void
    {
        $admin = User::first() ?? User::factory()->create(["email" => "admin@example.com"]);
        $tenantId = "demo-tenant"; // Привязка к дефолтному тенанту

        // Interior Designer Demo Sessions
        InteriorDesignSession::create([
            "user_id" => $admin->id,
            "tenant_id" => $tenantId,
            "correlation_id" => (string) Str::uuid(),
            "style" => "Nordic Minimalism",
            "budget_range" => "50000 - 150000",
            "results_json" => [
                "recommendations" => [
                    ["sku" => "IKEA-MALM-01", "name" => "Bed Frame", "price" => 25000],
                    ["sku" => "H&M-LIGHT-05", "name" => "Floor Lamp", "price" => 8000],
                ],
                "ai_suggestion" => "The room has high ceiling, we recommend warm ambient lighting.",
            ],
            "commission_status" => "pending",
        ]);

        // Beauty AI Demo Sessions
        BeautyTryOnSession::create([
            "user_id" => $admin->id,
            "tenant_id" => $tenantId,
            "correlation_id" => (string) Str::uuid(),
            "category" => "Hair Color",
            "params_json" => [
                "current_shade" => "Blonde",
                "target_shades" => ["Platinum", "Silver", "Icy Blonde"],
            ],
            "ai_analysis" => [
                "face_shape" => "Oval",
                "skin_tone" => "Cold",
                "recommendation" => "Platinum Blonde with roots shadow.",
            ],
            "order_linked" => false,
        ]);
    }
}

