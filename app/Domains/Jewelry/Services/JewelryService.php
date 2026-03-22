<?php declare(strict_types=1);

namespace App\Domains\Jewelry\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Jewelry\Models\JewelryItem;
use App\Domains\Jewelry\Models\JewelryOrder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class JewelryService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function orderItem(int $itemId, int $quantity, int $userId, int $tenantId): JewelryOrder
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($itemId, $quantity, $userId, $tenantId) {
            $item = JewelryItem::lockForUpdate()->find($itemId);
            
            if (!$item || $item->stock < $quantity) {
                throw new \Exception('Insufficient stock');
            }

            $order = JewelryOrder::create([
                'tenant_id' => $tenantId,
                'uuid' => Str::uuid(),
                'correlation_id' => $this->correlationId,
                'item_id' => $itemId,
                'user_id' => $userId,
                'quantity' => $quantity,
                'total_price' => $item->price * $quantity,
                'status' => 'pending',
            ]);

            Log::channel('audit')->info('Jewelry order created', [
                'correlation_id' => $this->correlationId,
                'item_id' => $itemId,
            ]);

            return $order;
        });
    }
}
