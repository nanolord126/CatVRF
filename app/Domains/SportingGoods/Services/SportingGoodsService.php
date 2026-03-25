declare(strict_types=1);

<?php
declare(strict_types=1);

namespace App\Domains\SportingGoods\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;
use App\Domains\SportingGoods\Models\SportProduct;

final readonly /**
 * SportingGoodsService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SportingGoodsService
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function __construct(
        private FraudControlService $fraudControlService
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

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
$this->db->transaction(function () use ($data, $correlationId) {
            $this->log->channel('audit')->info("СОЗДАНИЕ СПОРТТОВАРА", ["correlation_id" => $correlationId]);
            

            $product = SportProduct::create([
                "tenant_id" => tenant("id") ?? 1,
                "correlation_id" => $correlationId,
                "name" => $data["name"] ?? "Спорттовар",
                "price" => $data["price"] ?? 0,
                "tags" => []
            ]);

            $this->log->channel('audit')->info("СПОРТТОВАР СОЗДАН", ["correlation_id" => $correlationId, "id" => $product->id]);

            return $product;
        });
    }
}
