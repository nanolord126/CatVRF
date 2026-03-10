<?php

namespace App\Services\AI;

use App\Models\Tenant;
use App\Models\Inventory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AIBeautyTryOnService
{
    /**
     * Генерация превью прически, макияжа или ухода на основе фото клиента.
     */
    public function generateTryOn(Tenant $tenant, string $photoPath, string $type, array $params = [])
    {
        // 1. Replicate / HuggingFace API для генерации масок и изменений (Имитация 2026)
        $resultUrl = "https://cdn.ai-service.com/results/" . Str::random(32) . ".jpg";

        // 2. Получение списка товаров из инвентаря салона, которые будут использованы
        $inventoryItems = $this->getRequiredMaterials($tenant, $type, $params);

        return [
            "result_image" => $resultUrl,
            "materials" => $inventoryItems,
            "estimated_time" => $this->calculateTime($type, $params)
        ];
    }

    protected function getRequiredMaterials(Tenant $tenant, string $type, array $params)
    {
        // Поиск красок, шампуней или косметики в Inventory тенанта
        return DB::table("inventory")
            ->where("tenant_id", $tenant->id)
            ->where("category", $type === "makeup" ? "cosmetics" : "hair_care")
            ->take(3)
            ->get();
    }

    protected function calculateTime(string $type, array $params)
    {
        $times = ["hair" => 90, "makeup" => 45, "skin_care" => 30];
        return $times[$type] ?? 60;
    }
}
