<?php declare(strict_types=1);

namespace App\Domains\Archived\Taxi\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TaxiRideController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            private readonly TaxiSurgeService $surgeService,


            private readonly FraudControlService $fraudControlService,


        ) {}


        public function index(Request $request): JsonResponse


        {


            try {


                $correlationId = Str::uuid()->toString();


                $rides = TaxiRide::query()


                    ->with(['passenger', 'driver', 'vehicle'])


                    ->where('tenant_id', tenant('id'))


                    ->paginate(15);


                return response()->json([


                    'success' => true,


                    'data' => $rides,


                    'correlation_id' => $correlationId,


                ]);


            } catch (\Throwable $e) {


                Log::channel('audit')->error('Failed to fetch rides', [


                    'error' => $e->getMessage(),


                ]);


                return response()->json([


                    'success' => false,


                    'message' => 'Ошибка при получении поездок',


                ], 500);


            }


        }


        public function store(Request $request): JsonResponse


        {


            $correlationId = Str::uuid()->toString();


            $fraudResult = $this->fraudControlService->check(


                auth()->id() ?? 0,


                'taxi_ride_create',


                0,


                $request->ip(),


                $request->header('X-Device-Fingerprint'),


                $correlationId,


            );


            if ($fraudResult['decision'] === 'block') {


                Log::channel('fraud_alert')->warning('TaxiRide create blocked', [


                    'correlation_id' => $correlationId,


                    'user_id'        => auth()->id(),


                    'score'          => $fraudResult['score'],


                ]);


                return response()->json([


                    'success'        => false,


                    'error'          => 'Операция заблокирована.',


                    'correlation_id' => $correlationId,


                ], 403);


            }


            try {


                $validated = $request->validate([


                    'passenger_id'  => 'required|exists:users,id',


                    'driver_id'     => 'required|exists:taxi_drivers,id',


                    'vehicle_id'    => 'required|exists:taxi_vehicles,id',


                    'pickup_point'  => 'required|array',


                    'dropoff_point' => 'required|array',


                ]);


                $ride = DB::transaction(function () use ($validated, $correlationId) {


                    $surgeMultiplier = $this->surgeService->calculateSurgeMultiplier(


                        $validated['pickup_point'],


                        tenant('id'),


                        $correlationId,


                    );


                    $ride = TaxiRide::create([


                        'tenant_id'        => tenant('id'),


                        'passenger_id'     => $validated['passenger_id'],


                        'driver_id'        => $validated['driver_id'],


                        'vehicle_id'       => $validated['vehicle_id'],


                        'pickup_point'     => $validated['pickup_point'],


                        'dropoff_point'    => $validated['dropoff_point'],


                        'status'           => 'waiting',


                        'surge_multiplier' => $surgeMultiplier,


                        'base_price'       => 5000,


                        'total_price'      => 5000,


                        'correlation_id'   => $correlationId,


                    ]);


                    Log::channel('audit')->info('TaxiRide created', [


                        'ride_id'          => $ride->id,


                        'passenger_id'     => $ride->passenger_id,


                        'driver_id'        => $ride->driver_id,


                        'surge_multiplier' => $surgeMultiplier,


                        'correlation_id'   => $correlationId,


                    ]);


                    return $ride;


                });


                return response()->json([


                    'success'        => true,


                    'data'           => $ride,


                    'correlation_id' => $correlationId,


                ], 201);


            } catch (\Throwable $e) {


                Log::channel('audit')->error('Failed to create ride', [


                    'error'          => $e->getMessage(),


                    'trace'          => $e->getTraceAsString(),


                    'correlation_id' => $correlationId,


                ]);


                return response()->json([


                    'success' => false,


                    'message' => 'Ошибка при создании поездки',


                ], 500);


            }


        }


        public function show(TaxiRide $ride): JsonResponse


        {


            try {


                return response()->json([


                    'success' => true,


                    'data' => $ride->load(['passenger', 'driver', 'vehicle']),


                ]);


            } catch (\Throwable $e) {


                return response()->json([


                    'success' => false,


                    'message' => 'Поездка не найдена',


                ], 404);


            }


        }


        public function cancel(TaxiRide $ride, Request $request): JsonResponse


        {


            try {


                $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());


                $this->authorize('cancel', $ride);


                $ride = DB::transaction(function () use ($ride, $correlationId) {


                    $ride->update(['status' => 'cancelled']);


                    Log::channel('audit')->info('Ride cancelled', [


                        'ride_id' => $ride->id,


                        'correlation_id' => $correlationId,


                    ]);


                    return $ride;


                });


                return response()->json([


                    'success' => true,


                    'data' => $ride,


                    'correlation_id' => $correlationId,


                ]);


            } catch (\Throwable $e) {


                Log::channel('audit')->error('Failed to cancel ride', [


                    'error' => $e->getMessage(),


                ]);


                return response()->json([


                    'success' => false,


                    'message' => 'Ошибка при отмене поездки',


                ], 500);


            }


        }


        public function rate(TaxiRide $ride, Request $request): JsonResponse


        {


            try {


                $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());


                $validated = $request->validate([


                    'rating'  => 'required|integer|min:1|max:5',


                    'comment' => 'nullable|string|max:500',


                ]);


                $this->authorize('rate', $ride);


                $before = ['rating' => $ride->rating, 'comment' => $ride->comment ?? null];


                DB::transaction(function () use ($ride, $validated) {


                    $ride->update([


                        'rating'  => $validated['rating'],


                        'comment' => $validated['comment'] ?? null,


                    ]);


                });


                Log::channel('audit')->info('TaxiRide rated', [


                    'ride_id'        => $ride->id,


                    'before'         => $before,


                    'after'          => ['rating' => $validated['rating'], 'comment' => $validated['comment'] ?? null],


                    'correlation_id' => $correlationId,


                ]);


                return response()->json([


                    'success'        => true,


                    'message'        => 'Спасибо за оценку',


                    'correlation_id' => $correlationId,


                ]);


            } catch (\Throwable $e) {


                Log::error('TaxiRide rate failed', [


                    'ride_id' => $ride->id,


                    'error'   => $e->getMessage(),


                    'trace'   => $e->getTraceAsString(),


                ]);


                return response()->json([


                    'success' => false,


                    'message' => 'Ошибка при оценке поездки',


                ], 500);


            }


        }


        public function status(TaxiRide $ride): JsonResponse


        {


            return response()->json([


                'success' => true,


                'status' => $ride->status,


                'driver' => $ride->driver,


                'vehicle' => $ride->vehicle,


            ]);


        }
}
