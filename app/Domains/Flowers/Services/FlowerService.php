<?php declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use App\Services\{FraudControlService, WalletService, PaymentService};
use App\Domains\Flowers\Models\{Bouquet, FlowerOrder};
use Illuminate\Support\Facades\{DB, Log};
use Illuminate\Support\Str;

final class FlowerService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly PaymentService $payment,
    ) {}

    public function createOrder(array $data, bool $isB2B): array
    {
        $cid = Str::uuid()->toString();
        Log::channel('audit')->info('Flower order', compact('cid', 'isB2B'));
        $this->fraud->check(0, 'flower_order', 0, null, null, $cid);

        return DB::transaction(function () use ($data, $isB2B, $cid) {
            $bouquet = Bouquet::findOrFail($data['bouquet_id']);
            $price = $isB2B ? $bouquet->price * 0.85 : $bouquet->price;

            $order = FlowerOrder::create([
                'tenant_id' => tenant()->id,
                'bouquet_id' => $bouquet->id,
                'user_id' => $data['user_id'] ?? null,
                'inn' => $data['inn'] ?? null,
                'business_card_id' => $data['business_card_id'] ?? null,
                'total_price' => $price,
                'status' => 'pending',
                'correlation_id' => $cid,
            ]);

            $this->deductConsumables($bouquet, $cid);

            return ['order' => $order, 'correlation_id' => $cid];
        });
    }

    private function deductConsumables($bouquet, $cid): void
    {
        Log::channel('audit')->info('Deduct consumables', compact('cid'));
    }
}
