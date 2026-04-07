<?php declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class CourierRatingController extends Controller
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}


    public function getCourierRatings(int $courierId): JsonResponse
        {
            try {
                $ratings = CourierRating::where('courier_service_id', $courierId)
                    ->with('reviewer')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $ratings, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
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
                        'reviewer_id' => $request->user()?->id,
                        'rating' => $request->input('rating'),
                        'comment' => $request->input('comment'),
                        'verified_transaction' => true,
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Courier rated', [
                        'shipment_id' => $shipmentId,
                        'courier_id' => $shipment->courier_service_id,
                        'rating' => $request->input('rating'),
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }
}
