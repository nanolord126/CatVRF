<?php declare(strict_types=1);

namespace App\Domains\Jewelry\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Jewelry\Models\JewelryItem;
use App\Domains\Jewelry\Models\JewelryOrder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

final class JewelryService
{
    public function __construct(
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function orderItem(int $itemId, int $quantity): JewelryOrder
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'orderItem'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL orderItem', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($itemId, $quantity) {
            $item = JewelryItem::lockForUpdate()->find($itemId);
            
            if (!$item || $item->stock < $quantity) {
                throw new \Exception('Insufficient stock');
            }

            $order = JewelryOrder::create([
                'tenant_id' => auth()->user()->tenant_id,
                'uuid' => Str::uuid(),
                'correlation_id' => $this->correlationId,
                'item_id' => $itemId,
                'user_id' => auth()->id(),
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
