<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LogisticsService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    private string $correlationId;

        public function __construct(string $correlationId = null)
        {
            $this->correlationId = $correlationId ?? (string) Str::uuid();
        }

        /**
         * Расчет динамического коэффициента (Surge Pricing) для точки.
         *
         * @param array $point [lat, lon]
         * @param int $tenantId
         * @return float
         */
        public function calculateSurgeMultiplier(array $point, int $tenantId): float
        {
            Log::channel("audit")->info("Calculating surge multiplier", [
                "point" => $point,
                "tenant_id" => $tenantId,
                "correlation_id" => $this->correlationId
            ]);

            $activeSurge = SurgeZone::query()
                ->where("tenant_id", $tenantId)
                ->where("active_from", "<=", now())
                ->where("active_until", ">=", now())
                ->whereHas("geoZone", function($q) {
                    $q->where("is_active", true);
                })
                ->orderByDesc("multiplier")
                ->first();

            $multiplier = $activeSurge ? (float) $activeSurge->multiplier : 1.0;

            $onlineCouriers = Courier::where("tenant_id", $tenantId)->status("online")->count();
            $pendingOrders = DeliveryOrder::where("tenant_id", $tenantId)->where("status", "pending")->count();

            if ($onlineCouriers > 0 && ($pendingOrders / $onlineCouriers) > 5) {
                $multiplier += 0.5;
            }

            return round($multiplier, 2);
        }

        public function findOptimalCourier(DeliveryOrder $order): ?Courier
        {
            FraudControlService::check(["operation" => "courier_matching", "order_id" => $order->id]);

            return DB::transaction(function() use ($order) {
                $couriers = Courier::query()
                    ->where("tenant_id", $order->tenant_id)
                    ->status("online")
                    ->where("is_active", true)
                    ->with("vehicle")
                    ->get();

                if ($couriers->isEmpty()) {
                    Log::channel("audit")->warning("No online couriers available", [
                        "order_uuid" => $order->uuid,
                        "correlation_id" => $this->correlationId
                    ]);
                    return null;
                }

                $winner = $couriers->first();

                if ($winner) {
                    $order->update([
                        "courier_id" => $winner->id,
                        "status" => "assigned",
                        "correlation_id" => $this->correlationId
                    ]);

                    Log::channel("audit")->info("Courier assigned", [
                        "order_uuid" => $order->uuid,
                        "courier_uuid" => $winner->uuid,
                        "correlation_id" => $this->correlationId
                    ]);
                }

                return $winner;
            });
        }

        public function createDeliveryOrder(array $data, int $tenantId): DeliveryOrder
        {
            FraudControlService::check(["operation" => "create_delivery_order"]);

            return DB::transaction(function() use ($data, $tenantId) {
                $multiplier = $this->calculateSurgeMultiplier($data["pickup"], $tenantId);
                $totalPrice = (int) ($data["base_price"] * $multiplier);

                $order = DeliveryOrder::create([
                    "tenant_id" => $tenantId,
                    "source_order_id" => $data["source_order_id"],
                    "pickup_point" => $data["pickup"],
                    "dropoff_point" => $data["dropoff"],
                    "status" => "pending",
                    "base_price" => $data["base_price"],
                    "surge_multiplier" => $multiplier,
                    "total_price" => $totalPrice,
                    "correlation_id" => $this->correlationId,
                    "metadata" => $data["metadata"] ?? []
                ]);

                Log::channel("audit")->info("Delivery order created", [
                    "order_uuid" => $order->uuid,
                    "total_price" => $totalPrice,
                    "correlation_id" => $this->correlationId
                ]);

                return $order;
            });
        }
}
