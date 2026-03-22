<?php declare(strict_types=1);

namespace App\Domains\Pet\Http\Controllers;

use App\Domains\Pet\Models\PetClinic;
use App\Domains\Pet\Models\PetVet;
use App\Domains\Pet\Models\PetGroomingService;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PetClinicController extends Controller
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $clinics = PetClinic::where('is_active', true)
                ->where('is_verified', true)
                ->with(['vets', 'services', 'reviews'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $clinics,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to get clinics', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve clinics',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $clinic = PetClinic::with(['vets', 'services', 'reviews'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $clinic,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $this->authorize('create', PetClinic::class);

            $clinic = PetClinic::create([
                ...$request->validated(),
                'tenant_id' => tenant()->id,
                'owner_id' => auth()->id(),
                'correlation_id' => $correlationId,
                'uuid' => Str::uuid(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $clinic,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Failed to create clinic', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create clinic',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $clinic = PetClinic::findOrFail($id);
            $this->authorize('update', $clinic);

            $clinic->update([
                ...$request->validated(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $clinic,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update clinic',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function delete(Request $request, $id): JsonResponse
    {
        try {
            $clinic = PetClinic::findOrFail($id);
            $this->authorize('delete', $clinic);
            $correlationId = Str::uuid()->toString();

            $clinic->delete();

            return response()->json([
                'success' => true,
                'message' => 'Clinic deleted successfully',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete clinic',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function myList(): JsonResponse
    {
        try {
            $clinics = PetClinic::where('owner_id', auth()->id())
                ->where('tenant_id', tenant()->id)
                ->with(['vets', 'services', 'appointments'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $clinics,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve clinics',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function getVets($id): JsonResponse
    {
        try {
            $clinic = PetClinic::findOrFail($id);
            $vets = $clinic->vets()->with('reviews')->get();

            return response()->json([
                'success' => true,
                'data' => $vets,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve vets',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function getServices($id): JsonResponse
    {
        try {
            $clinic = PetClinic::findOrFail($id);
            $services = $clinic->services()->where('is_active', true)->get();

            return response()->json([
                'success' => true,
                'data' => $services,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve services',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function getReviews($id): JsonResponse
    {
        try {
            $clinic = PetClinic::findOrFail($id);
            $reviews = $clinic->reviews()->where('status', 'approved')->get();

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reviews',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function getVetsList(): JsonResponse
    {
        try {
            $vets = PetVet::where('is_active', true)
                ->with(['clinic', 'reviews'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $vets,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve vets',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function getVetDetail($id): JsonResponse
    {
        try {
            $vet = PetVet::with(['clinic', 'reviews', 'appointments'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $vet,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vet not found',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function getVetAppointments($id): JsonResponse
    {
        try {
            $vet = PetVet::findOrFail($id);
            $appointments = $vet->appointments()
                ->where('status', '!=', 'cancelled')
                ->orderBy('scheduled_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $appointments,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve appointments',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function getVetReviews($id): JsonResponse
    {
        try {
            $vet = PetVet::findOrFail($id);
            $reviews = $vet->reviews()->where('status', 'approved')->get();

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reviews',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function getServicesList(): JsonResponse
    {
        try {
            $services = PetGroomingService::where('is_active', true)
                ->with('clinic')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $services,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve services',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function getServiceDetail($id): JsonResponse
    {
        try {
            $service = PetGroomingService::with('clinic')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $service,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $query = PetClinic::query();

            if ($request->filled('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            if ($request->filled('address')) {
                $query->where('address', 'like', '%' . $request->address . '%');
            }

            $clinics = $query->where('is_verified', true)
                ->where('is_active', true)
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $clinics,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function filter(Request $request): JsonResponse
    {
        try {
            $query = PetClinic::query();

            if ($request->filled('rating_min')) {
                $query->where('rating', '>=', $request->rating_min);
            }

            if ($request->filled('is_verified')) {
                $query->where('is_verified', (bool)$request->is_verified);
            }

            $clinics = $query->where('is_active', true)->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $clinics,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Filter failed',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function verify(Request $request, $id): JsonResponse
    {
        try {
            $this->authorize('verify', PetClinic::class);
            $clinic = PetClinic::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            $clinic->update([
                'is_verified' => true,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $clinic,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify clinic',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $clinics = PetClinic::where('owner_id', auth()->id())
                ->where('tenant_id', tenant()->id)
                ->get();

            $stats = [
                'total_clinics' => $clinics->count(),
                'total_appointments' => $clinics->sum(fn($c) => $c->appointments()->count()),
                'total_vets' => $clinics->sum(fn($c) => $c->vets()->count()),
                'average_rating' => $clinics->avg('rating'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stats',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function analyticsClinic($id): JsonResponse
    {
        try {
            $clinic = PetClinic::findOrFail($id);

            $analytics = [
                'appointments_count' => $clinic->appointments()->count(),
                'completed_appointments' => $clinic->appointments()->where('status', 'completed')->count(),
                'avg_rating' => $clinic->rating,
                'reviews_count' => $clinic->reviews()->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get analytics',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function earnings($id): JsonResponse
    {
        try {
            $clinic = PetClinic::findOrFail($id);
            $this->authorize('update', $clinic);

            $earnings = [
                'total_commission' => $clinic->appointments()->where('payment_status', 'paid')->sum('commission_amount')
                    + $clinic->boardingReservations()->where('payment_status', 'paid')->sum('commission_amount'),
                'appointments_count' => $clinic->appointments()->where('payment_status', 'paid')->count(),
                'boarding_count' => $clinic->boardingReservations()->where('payment_status', 'paid')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $earnings,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get earnings',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function earningsReport(Request $request): JsonResponse
    {
        try {
            $clinics = PetClinic::where('tenant_id', tenant()->id)
                ->select(['id', 'name', 'owner_id'])
                ->get();

            $report = $clinics->map(function ($clinic) {
                return [
                    'clinic_id' => $clinic->id,
                    'clinic_name' => $clinic->name,
                    'total_earnings' => $clinic->appointments()->where('payment_status', 'paid')->sum('commission_amount')
                        + $clinic->boardingReservations()->where('payment_status', 'paid')->sum('commission_amount'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $report,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get report',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }
}
