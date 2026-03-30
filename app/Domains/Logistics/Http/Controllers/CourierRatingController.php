<?php declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourierRatingController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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

                DB::transaction(function () use ($shipmentId, $correlationId) {
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

                    Log::channel('audit')->info('Courier rated', [
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
