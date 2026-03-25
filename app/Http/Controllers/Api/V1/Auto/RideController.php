declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auto;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Auto\CreateRideRequest;
use App\Models\Auto\TaxiRide;
use App\Models\Auto\TaxiDriver;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Auto Taxi Ride API Controller.
 * Workflow: Create → Surge pricing → Payment init → Complete → Driver settlement.
 *
 * Commission: 15% platform (85% to driver), +5% fleet if applicable.
 * Surge pricing: 1.5x multiplier during peak demand.
 * Driver earnings: 85% of fare (before surge).
 * Fleet commission: 5% additional if driver in fleet.
 */
final class RideController extends BaseApiController
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly WalletService $walletService,
    ) {}

    /**
     * POST /api/v1/auto/rides
     * Создать поездку такси.
     *
     * @return JsonResponse
     */
    public function store(CreateRideRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();
        $tenantId = $request->getTenantId();

        try {
            return $this->db->transaction(function () use ($request, $correlationId, $tenantId) {
                // 1. Рассчитать цену поездки
                $basePrice = $request->integer('base_price', 100);
                $distanceKm = $request->integer('distance_km', 1);
                $pricePerKm = $request->integer('price_per_km', 50);
                
                $calculatedPrice = $basePrice + ($distanceKm * $pricePerKm);
                
                // 2. Surge pricing: 1.5x during peak hours (7-10, 17-20)
                $hour = now()->hour;
                $surgeMultiplier = ($hour >= 7 && $hour <= 10) || ($hour >= 17 && $hour <= 20) ? 1.5 : 1.0;
                $totalPrice = intdiv((int) ($calculatedPrice * $surgeMultiplier), 1);

                // 3. Fraud check на высокие суммы
                $fraudResult = $this->fraudService->scoreOperation([
                    'type' => 'taxi_ride',
                    'amount' => $totalPrice,
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);

                if ($fraudResult['decision'] === 'block') {
                    $this->log->channel('fraud_alert')->warning('Taxi ride blocked', [
                        'correlation_id' => $correlationId,
                        'amount' => $totalPrice,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Ride request blocked by fraud check',
                        'correlation_id' => $correlationId,
                    ], 403)->send();
                }

                // 4. Создать поездку
                $ride = TaxiRide::create([
                    'tenant_id' => $tenantId,
                    'passenger_id' => auth()->id(),
                    'driver_id' => $request->integer('driver_id'),
                    'vehicle_id' => $request->integer('vehicle_id'),
                    'pickup_address' => $request->input('pickup_address'),
                    'dropoff_address' => $request->input('dropoff_address'),
                    'distance_km' => $distanceKm,
                    'base_price' => $basePrice,
                    'surge_multiplier' => $surgeMultiplier,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                ]);

                // 5. Hold сумм в кошельке пассажира
                $this->walletService->reserveStock(
                    item_id: $ride->id,
                    quantity: $totalPrice,
                    source_type: 'taxi_ride',
                    source_id: $ride->id,
                    correlation_id: $correlationId,
                );

                // 6. Логирование
                $this->log->channel('audit')->info('Taxi ride created', [
                    'correlation_id' => $correlationId,
                    'ride_id' => $ride->id,
                    'passenger_id' => auth()->id(),
                    'distance_km' => $distanceKm,
                    'total_price' => $totalPrice,
                    'surge_multiplier' => $surgeMultiplier,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Ride request created',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'id' => $ride->id,
                        'uuid' => $ride->uuid,
                        'total_price' => $ride->total_price,
                        'surge_multiplier' => $ride->surge_multiplier,
                        'distance_km' => $ride->distance_km,
                    ],
                ], 201);
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Taxi ride creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ride creation failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /api/v1/auto/rides/{id}/complete
     * Завершить поездку и расчеты с водителем.
     */
    public function complete(TaxiRide $ride, CreateRideRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();

        try {
            return $this->db->transaction(function () use ($ride, $correlationId) {
                $ride->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                // Расчет с водителем: 85% от цены (15% платформе)
                $driverCommissionRate = 85.0;
                $platformCommissionRate = 15.0;
                
                $driverEarnings = intdiv((int) ($ride->total_price * $driverCommissionRate / 100), 1);
                $platformCommission = $ride->total_price - $driverEarnings;
                
                // Проверить, есть ли водитель в автопарке (+5% комиссия автопарку)
                $driver = TaxiDriver::find($ride->driver_id);
                if ($driver && $driver->fleet_id) {
                    $fleetCommissionRate = 5.0;
                    $fleetCommission = intdiv((int) ($ride->total_price * $fleetCommissionRate / 100), 1);
                    $driverEarnings -= $fleetCommission;
                    
                    $this->log->channel('audit')->info('Fleet commission deducted', [
                        'correlation_id' => $correlationId,
                        'fleet_id' => $driver->fleet_id,
                        'commission' => $fleetCommission,
                    ]);
                }

                // Кредитировать кошелёк водителя
                $driverWallet = $driver->wallet ?? \App\Models\Wallet\Wallet::factory()->create([
                    'tenant_id' => $ride->tenant_id,
                    'user_id' => $driver->user_id,
                ]);

                $this->walletService->credit(
                    wallet_id: $driverWallet->id,
                    amount: $driverEarnings,
                    reason: 'Taxi ride earnings',
                    correlation_id: $correlationId,
                );

                $this->log->channel('audit')->info('Taxi ride completed', [
                    'correlation_id' => $correlationId,
                    'ride_id' => $ride->id,
                    'total_price' => $ride->total_price,
                    'driver_earnings' => $driverEarnings,
                    'platform_commission' => $platformCommission,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Ride completed',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'id' => $ride->id,
                        'total_price' => $ride->total_price,
                        'driver_earnings' => $driverEarnings,
                        'platform_commission' => $platformCommission,
                    ],
                ], 200);
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Ride completion failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ride completion failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /api/v1/auto/rides/{id}/cancel
     * Отменить поездку (с комиссией отмены для водителя).
     */
    public function cancel(TaxiRide $ride, CreateRideRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();
        $cancellationFee = 5000; // 50 rubles in kopeks

        try {
            return $this->db->transaction(function () use ($ride, $correlationId, $cancellationFee) {
                $ride->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                // Вернуть деньги пассажиру минус комиссия отмены
                $refundAmount = $ride->total_price - $cancellationFee;
                $this->walletService->credit(
                    wallet_id: auth()->user()->wallet_id ?? 1,
                    amount: $refundAmount,
                    reason: 'Ride cancellation refund',
                    correlation_id: $correlationId,
                );

                // Водитель получает плату за отмену
                $driver = TaxiDriver::find($ride->driver_id);
                if ($driver) {
                    $driverWallet = $driver->wallet ?? \App\Models\Wallet\Wallet::factory()->create([
                        'tenant_id' => $ride->tenant_id,
                        'user_id' => $driver->user_id,
                    ]);

                    $this->walletService->credit(
                        wallet_id: $driverWallet->id,
                        amount: $cancellationFee,
                        reason: 'Ride cancellation fee',
                        correlation_id: $correlationId,
                    );
                }

                $this->log->channel('audit')->info('Taxi ride cancelled', [
                    'correlation_id' => $correlationId,
                    'ride_id' => $ride->id,
                    'passenger_refund' => $refundAmount,
                    'cancellation_fee' => $cancellationFee,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Ride cancelled',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'id' => $ride->id,
                        'passenger_refund' => $refundAmount,
                        'cancellation_fee' => $cancellationFee,
                    ],
                ], 200);
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Ride cancellation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Cancellation failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
