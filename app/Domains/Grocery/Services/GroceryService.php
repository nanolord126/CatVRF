<?php declare(strict_types=1);

namespace App\Domains\Grocery\Services;

use App\Services\{FraudControlService, WalletService, PaymentService};
use App\Domains\Grocery\Models\{GroceryStore, GroceryOrder};
use Illuminate\Support\Facades\{DB, Log};
use Illuminate\Support\Str;

final class GroceryService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly PaymentService $payment,
    ) {}

    public function createOrder(array $data, bool $isB2B): array
    {
        $cid = Str::uuid()->toString();
        Log::channel('audit')->info('Grocery order', compact('cid', 'isB2B'));
        $this->fraud->check(0, 'grocery_order', 0, null, null, $cid);

        return DB::transaction(function () use ($data, $isB2B, $cid) {
            $store = GroceryStore::findOrFail($data['store_id']);
            $total = collect($data['items'])->sum('price');
            $finalPrice = $isB2B ? $total * 0.88 : $total;

            $order = GroceryOrder::create([
                'tenant_id' => tenant()->id,
                'store_id' => $store->id,
                'user_id' => $data['user_id'] ?? null,
                'inn' => $data['inn'] ?? null,
                'business_card_id' => $data['business_card_id'] ?? null,
                'items' => $data['items'],
                'total_price' => $finalPrice,
                'delivery_address' => $data['delivery_address'],
                'delivery_slot' => $data['delivery_slot'],
                'status' => 'pending',
                'correlation_id' => $cid,
            ]);

            return ['order' => $order, 'correlation_id' => $cid];
        });
    }
}
