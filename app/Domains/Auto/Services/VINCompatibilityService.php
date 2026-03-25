<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;

use App\Domains\Auto\Models\AutoPartItem;
use App\Domains\Auto\Models\AutoPartOrder;
use App\Domains\AutoParts\Events\AutoPartOrderCreated;
use App\Services\FraudControlService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

final class VINCompatibilityService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function findCompatibleParts(string $vin, int $tenantId): Collection
    {


        try {
            $vehicles = json_decode($this->parseVINToJson($vin), true);

            $parts = AutoPartItem::where('tenant_id', $tenantId)
                ->whereJsonContains('compatible_vehicles', $vehicles)
                ->get();

            $this->log->channel('audit')->info('Compatible parts found', [
                'vin_last_4' => substr($vin, -4),
                'count' => $parts->count(),
                'tenant_id' => $tenantId,
            ]);

            return $parts;
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('VIN compatibility check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function createOrder(int $partId, string $vin, int $quantity, Carbon $deliveryDate, int $clientId, int $tenantId, string $correlationId): AutoPartOrder
    {


        return $this->db->transaction(function () use ($partId, $vin, $quantity, $deliveryDate, $clientId, $tenantId, $correlationId) {
            $this->fraudControlService->check(
                userId: $clientId,
                operationType: 'auto_part_order',
                amount: 0,
                correlationId: $correlationId,
            );

            $part = AutoPartItem::lockForUpdate()->findOrFail($partId);

            if ($part->current_stock < $quantity) {
                throw new \Exception("Insufficient stock for auto part {$partId}");
            }

            $totalPrice = $part->price * $quantity;

            $order = AutoPartOrder::create([
                'tenant_id' => $tenantId,
                'uuid' => Str::uuid(),
                'correlation_id' => $correlationId,
                'part_id' => $partId,
                'client_id' => $clientId,
                'vin' => $vin,
                'quantity' => $quantity,
                'total_price' => $totalPrice,
                'delivery_date' => $deliveryDate,
                'status' => 'pending',
                'idempotency_key' => md5("{$clientId}:{$partId}:{$vin}:{$quantity}:{$deliveryDate}:{$tenantId}"),
            ]);

            $part->decrement('current_stock', $quantity);

            AutoPartOrderCreated::dispatch($order->id, $tenantId, $clientId, $totalPrice, $correlationId);
            $this->log->channel('audit')->info('Auto part order created', [
                'order_id' => $order->id,
                'part_id' => $partId,
                'quantity' => $quantity,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    private function parseVINToJson(string $vin): string
    {
        // VIN format: positions 0-2 (WMI), 3-8 (VDS), 9-16 (VIS)
        // Simplified parsing for Russian market
        return json_encode([
            'manufacturer' => substr($vin, 0, 3),
            'year' => 2000 + (ord($vin[9]) - 48), // Simplified year extraction
        ]);
    }
}
