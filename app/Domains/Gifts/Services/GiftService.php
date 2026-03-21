<?php
declare(strict_types=1);

namespace App\Domains\Gifts\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Security\FraudControlService;
use App\Domains\Gifts\Models\GiftProduct;

final readonly class GiftService
{
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function createGift(array $data, string $correlationId): GiftProduct
    {
        return DB::transaction(function () use ($data, $correlationId) {
            Log::channel('audit')->info("СОЗДАНИЕ ПОДАРКА", ["correlation_id" => $correlationId]);
            
            FraudControlService::check($data, $correlationId);

            $product = GiftProduct::create([
                "tenant_id" => tenant("id") ?? 1,
                "correlation_id" => $correlationId,
                "name" => $data["name"] ?? "Подарок",
                "price" => $data["price"] ?? 0,
                "tags" => []
            ]);

            Log::channel('audit')->info("ПОДАРОК СОЗДАН", ["correlation_id" => $correlationId, "id" => $product->id]);

            return $product;
        });
    }
}
