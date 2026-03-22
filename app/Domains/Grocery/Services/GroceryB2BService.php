<?php declare(strict_types=1);

namespace App\Domains\Grocery\Services;

use App\Domains\Grocery\Models\GroceryStore;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class GroceryB2BService
{
    public function __construct(
        private readonly FraudControlService $fraudControl
    ) {}

    public function createB2BStore(array $data, string $correlationId): GroceryStore
    {
        return DB::transaction(function () use ($data, $correlationId) {
            $this->fraudControl->check($data, 'grocery_b2b_store_create');

            $store = GroceryStore::create(array_merge($data, [
                'correlation_id' => $correlationId,
            ]));

            Log::channel('audit')->info('Grocery B2B store created', [
                'store_id' => $store->id,
                'correlation_id' => $correlationId,
            ]);

            return $store;
        });
    }
}
