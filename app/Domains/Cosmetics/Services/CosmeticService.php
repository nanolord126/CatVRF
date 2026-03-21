<?php declare(strict_types=1);

namespace App\Domains\Cosmetics\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Cosmetics\Models\CosmeticProduct;
use App\Domains\Cosmetics\Models\CosmeticOrder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class CosmeticService
{
    public function __construct(
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function orderProduct(int $productId, int $quantity): CosmeticOrder
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'orderProduct'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL orderProduct', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'orderProduct'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL orderProduct', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'orderProduct'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL orderProduct', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($productId, $quantity) {
            $product = CosmeticProduct::lockForUpdate()->find($productId);
            
            if (!$product || $product->stock < $quantity) {
                throw new \Exception('Insufficient stock');
            }

            $order = CosmeticOrder::create([
                'tenant_id' => auth()->user()->tenant_id,
                'uuid' => Str::uuid(),
                'correlation_id' => $this->correlationId,
                'product_id' => $productId,
                'user_id' => auth()->id(),
                'quantity' => $quantity,
                'total_price' => $product->price * $quantity,
                'status' => 'pending',
            ]);

            Log::channel('audit')->info('Cosmetic order created', [
                'correlation_id' => $this->correlationId,
                'product_id' => $productId,
            ]);

            return $order;
        });
    }
}
