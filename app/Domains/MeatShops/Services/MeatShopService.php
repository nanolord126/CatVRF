<?php declare(strict_types=1);

namespace App\Domains\MeatShops\Services;

use App\Domains\MeatShops\Models\MeatShop;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class MeatShopService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet
    ) {}

    public function create(array $data, string $correlationId): MeatShop
    {
        $this->fraud->check($data);
        return DB::transaction(function () use ($data, $correlationId) {
            $shop = MeatShop::create(array_merge($data, ['correlation_id' => $correlationId]));
            Log::channel('audit')->info("MeatShop created", ['id' => $shop->id, 'correlation_id' => $correlationId]);
            return $shop;
        });
    }
}
