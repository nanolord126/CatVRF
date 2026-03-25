declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers;

use App\Domains\Logistics\Models\ShipmentTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final /**
 * ShipmentTrackingController
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ShipmentTrackingController
{
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
