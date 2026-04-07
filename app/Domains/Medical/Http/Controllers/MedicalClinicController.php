<?php declare(strict_types=1);

namespace App\Domains\Medical\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class MedicalClinicController extends Controller
{

    public function __construct(
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $clinics = MedicalClinic::where('is_active', true)
                    ->where('is_verified', true)
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $clinics,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                $this->logger->error('Error fetching clinics', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Failed to fetch clinics'], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $clinic = MedicalClinic::with(['doctors', 'services'])->findOrFail($id);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $clinic,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Clinic not found'], 404);
            }
        }

        public function doctors(int $id): JsonResponse
        {
            try {
                $clinic = MedicalClinic::findOrFail($id);
                $doctors = $clinic->doctors()->where('is_active', true)->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $doctors,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Not found'], 404);
            }
        }

        public function services(int $id): JsonResponse
        {
            try {
                $clinic = MedicalClinic::findOrFail($id);
                $services = $clinic->services()->where('is_active', true)->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $services,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Not found'], 404);
            }
        }

        public function reviews(int $id): JsonResponse
        {
            try {
                $clinic = MedicalClinic::findOrFail($id);
                $reviews = $clinic->reviews()->where('status', 'approved')->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reviews,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Not found'], 404);
            }
        }

        public function search(Request $request): JsonResponse
        {
            try {
                $query = MedicalClinic::where('is_active', true)->where('is_verified', true);

                if ($request->has('name')) {
                    $query->where('name', 'like', "%{$request->input('name')}%");
                }

                if ($request->has('specialization')) {
                    $query->whereJsonContains('specializations', $request->input('specialization'));
                }

                $clinics = $query->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $clinics,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Search failed'], 500);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $this->authorize('create', MedicalClinic::class);

                $clinic = MedicalClinic::create([
                    'tenant_id' => $request->user()->tenant_id,
                    'owner_id' => $request->user()->id,
                    'name' => $request->input('name'),
                    'description' => $request->input('description'),
                    'address' => $request->input('address'),
                    'phone' => $request->input('phone'),
                    'email' => $request->input('email'),
                    'specializations' => $request->input('specializations', []),
                    'is_active' => true,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);

                $this->logger->info('Clinic created', ['clinic_id' => $clinic->id]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $clinic,
                    'correlation_id' => $request->header('X-Correlation-ID'),
                ], 201);
            } catch (Throwable $e) {
                $this->logger->error('Failed to create clinic', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Failed to create clinic'], 500);
            }
        }

        public function myClinic(): JsonResponse
        {
            try {
                $clinic = MedicalClinic::where('owner_id', $request->user()->id)->first();

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $clinic,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Clinic not found'], 404);
            }
        }

        public function update(Request $request, int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $clinic = MedicalClinic::findOrFail($id);
                $this->authorize('update', $clinic);

                $clinic->update([
                    'name' => $request->input('name', $clinic->name),
                    'description' => $request->input('description', $clinic->description),
                    'phone' => $request->input('phone', $clinic->phone),
                    'email' => $request->input('email', $clinic->email),
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);

                $this->logger->info('Clinic updated', ['clinic_id' => $clinic->id]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $clinic]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Update failed'], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            try {
                $clinic = MedicalClinic::findOrFail($id);
                $this->authorize('delete', $clinic);

                $clinic->delete();

                $this->logger->info('Clinic deleted', ['clinic_id' => $clinic->id]);

                return new \Illuminate\Http\JsonResponse(['success' => true]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Delete failed'], 500);
            }
        }

        public function verify(Request $request, int $id): JsonResponse
        {
            try {
                $clinic = MedicalClinic::findOrFail($id);
                $clinic->update(['is_verified' => true]);

                $this->logger->info('Clinic verified', ['clinic_id' => $clinic->id]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $clinic]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Verification failed'], 500);
            }
        }

        public function all(): JsonResponse
        {
            try {
                $clinics = MedicalClinic::paginate(50);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $clinics,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Failed to fetch clinics'], 500);
            }
        }

        public function analytics(): JsonResponse
        {
            try {
                $month = now()->month;
                $year = now()->year;

                $clinics = MedicalClinic::with(['appointments' => function ($q) use ($month, $year) {
                    $q->whereMonth('scheduled_at', $month)->whereYear('scheduled_at', $year);
                }])->get();

                $analytics = $clinics->map(function ($clinic) {
                    return [
                        'clinic_id' => $clinic->id,
                        'name' => $clinic->name,
                        'appointments' => $clinic->appointments->count(),
                        'revenue' => $clinic->appointments->sum('price'),
                        'rating' => $clinic->rating,
                    ];
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $analytics,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Analytics failed'], 500);
            }
        }
}
