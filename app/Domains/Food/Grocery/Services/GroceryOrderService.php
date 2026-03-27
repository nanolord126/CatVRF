<?php

declare(strict_types=1);


namespace App\Domains\Food\Grocery\Services;

use App\Domains\Food\Grocery\Models\GroceryOrder;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly /**
 * GroceryOrderService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class GroceryOrderService
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function createOrder(array $data, bool $isB2B = false): GroceryOrder
    {
        $correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

        Log::channel('audit')->info('Grocery order started', [
            'correlation_id' => $correlationId,
            'is_b2b' => $isB2B,
        ]);

        $this->fraudControlService->check([
            'operation' => 'grocery_order_create',
            'user_id' => $data['user_id'] ?? null,
            'correlation_id' => $correlationId,
        ]);

        return DB::transaction(function () use ($data, $correlationId) {
            $order = GroceryOrder::create([
                ...$data,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $correlationId,
                'status' => 'pending',
            ]);

            Log::channel('audit')->info('Grocery order created', [
                'order_id' => $order->id,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }
}
