<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services;

use Modules\CatCRM\Domain\Entities\B2BLead;
use Modules\Inventory\Domain\Entities\InventoryRequest;
use Illuminate\Database\DatabaseManager;

final class CRMInventoryIntegrationService
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}
    public function createQuoteRequest(B2BLead $lead, array $items): InventoryRequest
    {
        return $this->db->transaction(function () use ($lead, $items) {
            $request = InventoryRequest::create([
                'tenant_id' => $lead->tenant_id,
                'b2b_lead_id' => $lead->id,
                'request_type' => 'quote',
                'status' => 'pending',
                'requested_by' => auth()->id(),
                'items' => $items,
                'metadata' => [
                    'lead_company' => $lead->company_name,
                    'lead_category' => $lead->category,
                    'correlation_id' => $lead->correlation_id,
                ],
            ]);

            return $request;
        });
    }

    public function processQuoteRequest(InventoryRequest $request): array
    {
        if ($request->status !== 'pending') {
            throw new \RuntimeException('Request is not in pending state');
        }

        return $this->db->transaction(function () use ($request) {
            $availability = [];
            $totalValue = 0;

            foreach ($request->items as $item) {
                $stock = \Modules\Inventory\Domain\Entities\Stock::where('sku', $item['sku'])
                    ->where('quantity', '>=', $item['quantity'])
                    ->first();

                $availability[$item['sku']] = [
                    'available' => $stock !== null,
                    'quantity' => $stock ? $stock->quantity : 0,
                    'unit_price' => $stock ? $stock->unit_price : 0,
                ];

                if ($stock) {
                    $totalValue += $stock->unit_price * $item['quantity'];
                }
            }

            $request->update([
                'status' => 'processed',
                'response_data' => [
                    'availability' => $availability,
                    'total_value' => $totalValue,
                    'processed_at' => now()->toIso8601String(),
                ],
            ]);

            return [
                'availability' => $availability,
                'total_value' => $totalValue,
                'can_fulfill' => collect($availability)->every(fn($i) => $i['available']),
            ];
        });
    }

    public function createInventoryReservation(B2BLead $lead, array $items, int $warehouseId): bool
    {
        return $this->db->transaction(function () use ($lead, $items, $warehouseId) {
            foreach ($items as $item) {
                $stock = \Modules\Inventory\Domain\Entities\Stock::where('warehouse_id', $warehouseId)
                    ->where('sku', $item['sku'])
                    ->lockForUpdate()
                    ->first();

                if (!$stock || $stock->quantity < $item['quantity']) {
                    throw new \RuntimeException("Insufficient stock for SKU: {$item['sku']}");
                }

                $stock->decrement('quantity', $item['quantity']);
                $stock->increment('reserved_quantity', $item['quantity']);
            }

            $lead->update(['default_warehouse_id' => $warehouseId]);

            return true;
        });
    }

    public function getInventoryForecast(int $tenantId, string $vertical, int $days = 30): array
    {
        $leads = B2BLead::where('tenant_id', $tenantId)
            ->where('vertical_id', $vertical)
            ->whereIn('status', ['qualified', 'proposal'])
            ->whereBetween('expected_close_date', [now(), now()->addDays($days)])
            ->get();

        $forecast = [];
        foreach ($leads as $lead) {
            $estimatedValue = $lead->estimateValue();
            $forecast[] = [
                'lead_id' => $lead->id,
                'company' => $lead->company_name,
                'expected_close' => $lead->expected_close_date,
                'estimated_value' => $estimatedValue,
                'probability' => $lead->probability ?? 50,
                'weighted_value' => $estimatedValue * ($lead->probability ?? 50) / 100,
            ];
        }

        return [
            'leads' => $forecast,
            'total_estimated' => collect($forecast)->sum('estimated_value'),
            'total_weighted' => collect($forecast)->sum('weighted_value'),
        ];
    }
}
