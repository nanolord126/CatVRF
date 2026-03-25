declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers;

use App\Domains\Logistics\Models\CourierRating;
use App\Domains\Logistics\Models\CourierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final /**
 * CourierRatingController
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CourierRatingController
{
    public function getCourierRatings(int $courierId): JsonResponse
    {
        try {
            $ratings = CourierRating::where('courier_service_id', $courierId)
                ->with('reviewer')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $ratings, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function rateShipment(int $shipmentId): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($shipmentId, $correlationId) {
                $shipment = \App\Domains\Logistics\Models\Shipment::findOrFail($shipmentId);

                CourierRating::create([
                    'tenant_id' => $shipment->tenant_id,
                    'courier_service_id' => $shipment->courier_service_id,
                    'reviewer_id' => auth()->id(),
                    'rating' => request('rating'),
                    'comment' => request('comment'),
                    'verified_transaction' => true,
                    'correlation_id' => $correlationId,
                ]);

                $this->log->channel('audit')->info('Courier rated', [
                    'shipment_id' => $shipmentId,
                    'courier_id' => $shipment->courier_service_id,
                    'rating' => request('rating'),
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
        }
    }
}
