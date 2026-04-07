<?php declare(strict_types=1);

namespace App\Domains\Medical\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class MedicalDoctorController extends Controller
{

    public function __construct(
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(Request $request): JsonResponse
        {
            try {
                $query = MedicalDoctor::where('is_active', true);

                if ($request->has('specialization')) {
                    $query->where('specialization', $request->input('specialization'));
                }

                if ($request->has('clinic_id')) {
                    $query->where('clinic_id', $request->input('clinic_id'));
                }

                $doctors = $query->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $doctors,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Failed to fetch doctors'], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $doctor = MedicalDoctor::with(['clinic', 'reviews'])->findOrFail($id);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $doctor,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Doctor not found'], 404);
            }
        }

        public function reviews(int $id): JsonResponse
        {
            try {
                $doctor = MedicalDoctor::findOrFail($id);
                $reviews = $doctor->reviews()->where('status', 'approved')->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reviews,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Not found'], 404);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $this->authorize('create', MedicalDoctor::class);

                $doctor = MedicalDoctor::create([
                    'tenant_id' => $request->user()->tenant_id,
                    'clinic_id' => $request->input('clinic_id'),
                    'user_id' => $request->user()->id,
                    'full_name' => $request->input('full_name'),
                    'specialization' => $request->input('specialization'),
                    'experience_years' => $request->input('experience_years', 0),
                    'bio' => $request->input('bio'),
                    'license_number' => $request->input('license_number'),
                    'phone' => $request->input('phone'),
                    'consultation_price' => $request->input('consultation_price', 0),
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);

                $this->logger->info('Doctor created', ['doctor_id' => $doctor->id]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $doctor,
                    'correlation_id' => $request->header('X-Correlation-ID'),
                ], 201);
            } catch (Throwable $e) {
                $this->logger->error('Failed to create doctor', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Failed to create doctor'], 500);
            }
        }

        public function myProfile(): JsonResponse
        {
            try {
                $doctor = MedicalDoctor::where('user_id', $request->user()->id)->first();

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $doctor,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Doctor not found'], 404);
            }
        }

        public function update(Request $request, int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $doctor = MedicalDoctor::findOrFail($id);
                $this->authorize('update', $doctor);

                $doctor->update([
                    'full_name' => $request->input('full_name', $doctor->full_name),
                    'bio' => $request->input('bio', $doctor->bio),
                    'consultation_price' => $request->input('consultation_price', $doctor->consultation_price),
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);

                $this->logger->info('Doctor updated', ['doctor_id' => $doctor->id]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $doctor]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Update failed'], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            try {
                $doctor = MedicalDoctor::findOrFail($id);
                $this->authorize('delete', $doctor);

                $doctor->update(['is_active' => false]);

                $this->logger->info('Doctor deactivated', ['doctor_id' => $doctor->id]);

                return new \Illuminate\Http\JsonResponse(['success' => true]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Delete failed'], 500);
            }
        }

        public function all(): JsonResponse
        {
            try {
                $doctors = MedicalDoctor::paginate(50);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $doctors,
                    'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Failed to fetch doctors'], 500);
            }
        }

        public function analytics(): JsonResponse
        {
            try {
                $doctors = MedicalDoctor::with(['appointments', 'reviews'])->get();

                $analytics = $doctors->map(function ($doctor) {
                    return [
                        'doctor_id' => $doctor->id,
                        'name' => $doctor->full_name,
                        'specialization' => $doctor->specialization,
                        'appointments' => $doctor->appointments->count(),
                        'revenue' => $doctor->appointments->sum('price'),
                        'rating' => $doctor->rating,
                        'reviews' => $doctor->reviews->count(),
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
