<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class TaxiDriverController extends Controller
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(Request $request): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                $drivers = TaxiDriver::query()
                    ->where('tenant_id', tenant()?->id)
                    ->where('is_active', true)
                    ->with(['vehicles', 'rides'])
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $drivers,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to fetch drivers', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при получении водителей',
                ], 500);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $correlationId = Str::uuid()->toString();

                $request->validate([
                    'user_id' => 'required|exists:users,id',
                    'license_number' => 'required|unique:taxi_drivers',
                ]);

                $validated = $request->all();
                $driver = $this->db->transaction(function () use ($validated, $correlationId) {
                    $driver = TaxiDriver::create([
                        'tenant_id' => tenant()?->id,
                        'user_id' => ($validated['user_id'] ?? null),
                        'license_number' => ($validated['license_number'] ?? null),
                        'rating' => 5.0,
                        'completed_rides' => 0,
                        'is_active' => true,
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Driver created', [
                        'driver_id' => $driver->id,
                        'user_id' => $driver->user_id,
                        'correlation_id' => $correlationId,
                    ]);

                    return $driver;
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $driver,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to create driver', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при создании водителя',
                ], 500);
            }
        }

        public function show(TaxiDriver $driver): JsonResponse
        {
            try {
                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $driver->load(['vehicles', 'user']),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Водитель не найден',
                ], 404);
            }
        }

        public function updateLocation(TaxiDriver $driver, Request $request): JsonResponse
        {
            try {
                $this->authorize('update', $driver);

                $request->validate([
                    'latitude' => 'required|numeric',
                    'longitude' => 'required|numeric',
                ]);

                $driver->update([
                    'current_location' => [
                        'lat' => $request->get('latitude'),
                        'lng' => $request->get('longitude'),
                    ],
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Локация обновлена',
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при обновлении локации',
                ], 500);
            }
        }

        public function getLocation(TaxiDriver $driver): JsonResponse
        {
            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'location' => $driver->current_location,
            ]);
        }

        public function deactivate(TaxiDriver $driver): JsonResponse
        {
            try {
                $this->authorize('deactivate', $driver);

                $driver->update(['is_active' => false]);

                $this->logger->info('Driver deactivated', [
                    'driver_id' => $driver->id,
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Водитель деактивирован',
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при деактивации',
                ], 500);
            }
        }

        public function activate(TaxiDriver $driver): JsonResponse
        {
            try {
                $this->authorize('deactivate', $driver);

                $driver->update(['is_active' => true]);

                $this->logger->info('Driver activated', [
                    'driver_id' => $driver->id,
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Водитель активирован',
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при активации',
                ], 500);
            }
        }

        public function list(Request $request): JsonResponse
        {
            $drivers = TaxiDriver::query()
                ->where('is_active', true)
                ->select(['id', 'rating', 'completed_rides', 'current_location'])
                ->paginate(50);

            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => $drivers,
            ]);
        }
}
