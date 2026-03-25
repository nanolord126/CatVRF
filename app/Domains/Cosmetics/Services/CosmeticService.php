declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Cosmetics\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Cosmetics\Models\CosmeticProduct;
use App\Domains\Cosmetics\Models\CosmeticOrder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final /**
 * CosmeticService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CosmeticService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function orderProduct(int $productId, int $quantity, int $userId, int $tenantId): CosmeticOrder
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
$this->db->transaction(function () use ($productId, $quantity, $userId, $tenantId) {
            $product = CosmeticProduct::lockForUpdate()->find($productId);
            
            if (!$product || $product->stock < $quantity) {
                throw new \Exception('Insufficient stock');
            }

            $order = CosmeticOrder::create([
                'tenant_id' => $tenantId,
                'uuid' => Str::uuid(),
                'correlation_id' => $this->correlationId,
                'product_id' => $productId,
                'user_id' => $userId,
                'quantity' => $quantity,
                'total_price' => $product->price * $quantity,
                'status' => 'pending',
            ]);

            $this->log->channel('audit')->info('Cosmetic order created', [
                'correlation_id' => $this->correlationId,
                'product_id' => $productId,
            ]);

            return $order;
        });
    }
}
