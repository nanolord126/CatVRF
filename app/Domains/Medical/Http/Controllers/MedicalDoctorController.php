<?php declare(strict_types=1);

namespace App\Domains\Medical\Http\Controllers;

use App\Domains\Medical\Models\MedicalDoctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

final class MedicalDoctorController
{
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

            return response()->json([
                'success' => true,
                'data' => $doctors,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Failed to fetch doctors'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $doctor = MedicalDoctor::with(['clinic', 'reviews'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $doctor,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Doctor not found'], 404);
        }
    }

    public function reviews(int $id): JsonResponse
    {
        try {
            $doctor = MedicalDoctor::findOrFail($id);
            $reviews = $doctor->reviews()->where('status', 'approved')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Not found'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $this->authorize('create', MedicalDoctor::class);

            $doctor = MedicalDoctor::create([
                'tenant_id' => auth()->user()->tenant_id,
                'clinic_id' => $request->input('clinic_id'),
                'user_id' => auth()->user()->id,
                'full_name' => $request->input('full_name'),
                'specialization' => $request->input('specialization'),
                'experience_years' => $request->input('experience_years', 0),
                'bio' => $request->input('bio'),
                'license_number' => $request->input('license_number'),
                'phone' => $request->input('phone'),
                'consultation_price' => $request->input('consultation_price', 0),
                'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);

            Log::channel('audit')->info('Doctor created', ['doctor_id' => $doctor->id]);

            return response()->json([
                'success' => true,
                'data' => $doctor,
                'correlation_id' => $request->header('X-Correlation-ID'),
            ], 201);
        } catch (Throwable $e) {
            Log::error('Failed to create doctor', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to create doctor'], 500);
        }
    }

    public function myProfile(): JsonResponse
    {
        try {
            $doctor = MedicalDoctor::where('user_id', auth()->user()->id)->first();

            return response()->json([
                'success' => true,
                'data' => $doctor,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Doctor not found'], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $doctor = MedicalDoctor::findOrFail($id);
            $this->authorize('update', $doctor);

            $doctor->update([
                'full_name' => $request->input('full_name', $doctor->full_name),
                'bio' => $request->input('bio', $doctor->bio),
                'consultation_price' => $request->input('consultation_price', $doctor->consultation_price),
                'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);

            Log::channel('audit')->info('Doctor updated', ['doctor_id' => $doctor->id]);

            return response()->json(['success' => true, 'data' => $doctor]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Update failed'], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $doctor = MedicalDoctor::findOrFail($id);
            $this->authorize('delete', $doctor);

            $doctor->update(['is_active' => false]);

            Log::channel('audit')->info('Doctor deactivated', ['doctor_id' => $doctor->id]);

            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Delete failed'], 500);
        }
    }

    public function all(): JsonResponse
    {
        try {
            $doctors = MedicalDoctor::paginate(50);

            return response()->json([
                'success' => true,
                'data' => $doctors,
                'correlation_id' => request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Failed to fetch doctors'], 500);
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
