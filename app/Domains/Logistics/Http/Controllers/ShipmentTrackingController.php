<?php declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShipmentTrackingController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function getHistory(int $shipmentId): JsonResponse
        {
            try {
                $history = ShipmentTracking::where('shipment_id', $shipmentId)
                    ->orderBy('event_time', 'desc')
                    ->get();

                return response()->json(['success' => true, 'data' => $history, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
