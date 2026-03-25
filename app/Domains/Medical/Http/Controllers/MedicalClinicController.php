<?php declare(strict_types=1);

namespace App\Domains\Medical\Http\Controllers;

use App\Domains\Medical\Models\MedicalClinic;
use App\Domains\Medical\Models\MedicalAppointment;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class MedicalClinicController
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $clinics = MedicalClinic::where('is_active', true)
                ->where('is_verified', true)
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $clinics,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            $this->log->error('Error fetching clinics', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to fetch clinics'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $clinic = MedicalClinic::with(['doctors', 'services'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $clinic,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Clinic not found'], 404);
        }
    }

    public function doctors(int $id): JsonResponse
    {
        try {
            $clinic = MedicalClinic::findOrFail($id);
            $doctors = $clinic->doctors()->where('is_active', true)->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $doctors,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Not found'], 404);
        }
    }

    public function services(int $id): JsonResponse
    {
        try {
            $clinic = MedicalClinic::findOrFail($id);
            $services = $clinic->services()->where('is_active', true)->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $services,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Not found'], 404);
        }
    }

    public function reviews(int $id): JsonResponse
    {
        try {
            $clinic = MedicalClinic::findOrFail($id);
            $reviews = $clinic->reviews()->where('status', 'approved')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Not found'], 404);
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

            return response()->json([
                'success' => true,
                'data' => $clinics,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Search failed'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $this->authorize('create', MedicalClinic::class);

            $clinic = MedicalClinic::create([
                'tenant_id' => auth()->user()->tenant_id,
                'owner_id' => auth()->user()->id,
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'address' => $request->input('address'),
                'phone' => $request->input('phone'),
                'email' => $request->input('email'),
                'specializations' => $request->input('specializations', []),
                'is_active' => true,
                'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);

            $this->log->channel('audit')->info('Clinic created', ['clinic_id' => $clinic->id]);

            return response()->json([
                'success' => true,
                'data' => $clinic,
                'correlation_id' => $request->header('X-Correlation-ID'),
            ], 201);
        } catch (Throwable $e) {
            $this->log->error('Failed to create clinic', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to create clinic'], 500);
        }
    }

    public function myClinic(): JsonResponse
    {
        try {
            $clinic = MedicalClinic::where('owner_id', auth()->user()->id)->first();

            return response()->json([
                'success' => true,
                'data' => $clinic,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Clinic not found'], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

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

            $this->log->channel('audit')->info('Clinic updated', ['clinic_id' => $clinic->id]);

            return response()->json(['success' => true, 'data' => $clinic]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Update failed'], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $clinic = MedicalClinic::findOrFail($id);
            $this->authorize('delete', $clinic);

            $clinic->delete();

            $this->log->channel('audit')->info('Clinic deleted', ['clinic_id' => $clinic->id]);

            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Delete failed'], 500);
        }
    }

    public function verify(Request $request, int $id): JsonResponse
    {
        try {
            $clinic = MedicalClinic::findOrFail($id);
            $clinic->update(['is_verified' => true]);

            $this->log->channel('audit')->info('Clinic verified', ['clinic_id' => $clinic->id]);

            return response()->json(['success' => true, 'data' => $clinic]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Verification failed'], 500);
        }
    }

    public function all(): JsonResponse
    {
        try {
            $clinics = MedicalClinic::paginate(50);

            return response()->json([
                'success' => true,
                'data' => $clinics,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Failed to fetch clinics'], 500);
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

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Analytics failed'], 500);
        }
    }
}
