<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class TravelTransportationController extends Controller
{

    public function __construct(private readonly TransportationService $transportationService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(Request $request): JsonResponse
        {
            try {
                $page = $request->get('page', 1);
                $per_page = $request->get('per_page', 20);
                $type = $request->get('type');

                $query = TravelTransportation::query()
                    ->where('tenant_id', tenant()->id)
                    ->where('status', 'available');

                if ($type) {
                    $query->where('type', $type);
                }

                $transportation = $query->paginate($per_page, ['*'], 'page', $page);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $transportation->items(),
                    'pagination' => [
                        'total' => $transportation->total(),
                    ],
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to list transportation',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $transportation = TravelTransportation::where('tenant_id', tenant()->id)->findOrFail($id);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $transportation,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Transportation not found',
                    'correlation_id' => Str::uuid(),
                ], 404);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id', Str::uuid()->toString());
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'transport_store', amount: 0, correlationId: $correlationId ?? '');

            try {
                $request->validate([
                    'type' => 'required|in:car_rental,bus,train,taxi,shuttle',
                    'provider' => 'required|string|max:255',
                    'location_pickup' => 'required|string',
                    'location_dropoff' => 'required|string',
                    'pickup_time' => 'required|date_format:Y-m-d H:i:s',
                    'dropoff_time' => 'required|date_format:Y-m-d H:i:s|after:pickup_time',
                    'capacity' => 'required|integer|min:1',
                    'price' => 'required|numeric|min:0',
                ]);

                $validated = $request->all();
                $transportation = $this->db->transaction(function () use ($validated, $correlationId) {
                    return TravelTransportation::create([
                        'tenant_id' => tenant()->id,
                        'type' => ($validated['type'] ?? null),
                        'provider' => ($validated['provider'] ?? null),
                        'location_pickup' => ($validated['location_pickup'] ?? null),
                        'location_dropoff' => ($validated['location_dropoff'] ?? null),
                        'pickup_time' => ($validated['pickup_time'] ?? null),
                        'dropoff_time' => ($validated['dropoff_time'] ?? null),
                        'capacity' => ($validated['capacity'] ?? null),
                        'available_count' => ($validated['capacity'] ?? null),
                        'price' => ($validated['price'] ?? null),
                        'commission_amount' => ($validated['price'] ?? null) * 0.14,
                        'features' => ($validated['features'] ?? []),
                        'status' => 'available',
                        'correlation_id' => $correlationId,
                        'uuid' => Str::uuid(),
                    ]);
                });

                $this->logger->info('Transportation created', [
                    'transportation_id' => $transportation->id,
                    'type' => $transportation->type,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $transportation,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to create transportation',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function update(Request $request, int $id): JsonResponse
        {
            $correlationId = $request->get('correlation_id', Str::uuid()->toString());
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'transport_update', amount: 0, correlationId: $correlationId ?? '');

            try {
                $transportation = TravelTransportation::where('tenant_id', tenant()->id)->findOrFail($id);

                $this->authorize('update', $transportation);

                $validated = $request->all();
                $transportation = $this->db->transaction(function () use ($validated, $transportation, $correlationId) {
                    $transportation->update([
                        'price' => ($validated['price'] ?? $transportation->price),
                        'status' => ($validated['status'] ?? $transportation->status),
                        'features' => ($validated['features'] ?? $transportation->features),
                        'correlation_id' => $correlationId,
                    ]);

                    return $transportation;
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $transportation,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to update transportation',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function destroy(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'transport_destroy', amount: 0, correlationId: $correlationId ?? '');

            try {
                $transportation = TravelTransportation::where('tenant_id', tenant()->id)->findOrFail($id);

                $this->authorize('delete', $transportation);

                $this->db->transaction(function () use ($transportation) {
                    $transportation->delete();
                });

                $this->logger->info('Transportation deleted', [
                    'transportation_id' => $transportation->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete transportation',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
