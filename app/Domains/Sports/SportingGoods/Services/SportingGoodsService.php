<?php declare(strict_types=1);

namespace App\Domains\Sports\SportingGoods\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SportingGoodsService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here
        public function __construct(
            private FraudControlService $fraudControlService
        ) {}

        public function createProduct(array $data, string $correlationId): SportProduct
        {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($data, $correlationId) {
                Log::channel('audit')->info("СОЗДАНИЕ СПОРТТОВАРА", ["correlation_id" => $correlationId]);


                $product = SportProduct::create([
                    "tenant_id" => tenant("id") ?? 1,
                    "correlation_id" => $correlationId,
                    "name" => $data["name"] ?? "Спорттовар",
                    "price" => $data["price"] ?? 0,
                    "tags" => []
                ]);

                Log::channel('audit')->info("СПОРТТОВАР СОЗДАН", ["correlation_id" => $correlationId, "id" => $product->id]);

                return $product;
            });
        }
}
