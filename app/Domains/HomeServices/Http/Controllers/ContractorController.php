<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ContractorController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function index(): JsonResponse
        {
            try {
                $contractors = Contractor::where('is_verified', true)
                    ->where('is_active', true)
                    ->with(['serviceListings', 'reviews'])
                    ->paginate(20);

                return response()->json([
                    'success' => true,
                    'data' => $contractors,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Failed to list contractors'], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $contractor = Contractor::with(['serviceListings', 'reviews', 'schedules'])->findOrFail($id);
                return response()->json(['success' => true, 'data' => $contractor, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Contractor not found'], 404);
            }
        }

        public function register(): JsonResponse
        {
            try {
                $this->authorize('create', Contractor::class);

                $validated = request()->validate([
                    'company_name' => 'required|string|max:255',
                    'description' => 'required|string',
                    'services' => 'nullable|array',
                    'phone' => 'nullable|string',
                    'website' => 'nullable|url',
                    'hourly_rate' => 'required|numeric|min:0',
                ]);

                $correlationId = Str::uuid()->toString();

                $contractor = Contractor::create([
                    'tenant_id' => tenant('id'),
                    'user_id' => auth()->id(),
                    'company_name' => $validated['company_name'],
                    'description' => $validated['description'],
                    'services' => $validated['services'] ?? [],
                    'phone' => $validated['phone'],
                    'website' => $validated['website'],
                    'hourly_rate' => $validated['hourly_rate'],
                    'correlation_id' => $correlationId,
                ]);

                \Log::channel('audit')->info('Contractor registered', [
                    'contractor_id' => $contractor->id,
                    'user_id' => auth()->id(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json(['success' => true, 'data' => $contractor, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Registration failed'], 500);
            }
        }

        public function myProfile(): JsonResponse
        {
            try {
                $contractor = Contractor::where('user_id', auth()->id())->firstOrFail();
                return response()->json(['success' => true, 'data' => $contractor, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Contractor profile not found'], 404);
            }
        }

        public function updateProfile(): JsonResponse
        {
            try {
                $contractor = Contractor::where('user_id', auth()->id())->firstOrFail();
                $validated = request()->validate([
                    'company_name' => 'sometimes|string',
                    'description' => 'sometimes|string',
                    'phone' => 'nullable|string',
                    'website' => 'nullable|url',
                    'hourly_rate' => 'sometimes|numeric|min:0',
                ]);

                $correlationId = Str::uuid()->toString();
                $contractor->update($validated + ['correlation_id' => $correlationId]);

                return response()->json(['success' => true, 'data' => $contractor, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Update failed'], 500);
            }
        }

        public function myEarnings(): JsonResponse
        {
            try {
                $contractor = Contractor::where('user_id', auth()->id())->firstOrFail();
                $earnings = ContractorEarning::where('contractor_id', $contractor->id)
                    ->orderByDesc('period_year')
                    ->orderByDesc('period_month')
                    ->paginate(12);

                return response()->json(['success' => true, 'data' => $earnings, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Failed to fetch earnings'], 500);
            }
        }

        public function getSchedule(): JsonResponse
        {
            try {
                $contractor = Contractor::where('user_id', auth()->id())->firstOrFail();
                $schedule = $contractor->schedules()->get();

                return response()->json(['success' => true, 'data' => $schedule, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Failed to fetch schedule'], 500);
            }
        }

        public function updateSchedule(): JsonResponse
        {
            try {
                $contractor = Contractor::where('user_id', auth()->id())->firstOrFail();
                $validated = request()->validate([
                    'schedule' => 'required|array',
                    'schedule.*.day_of_week' => 'required|string',
                    'schedule.*.start_time' => 'required|date_format:H:i',
                    'schedule.*.end_time' => 'required|date_format:H:i',
                    'schedule.*.is_available' => 'boolean',
                ]);

                \DB::transaction(function () use ($contractor, $validated) {
                    $contractor->schedules()->delete();
                    foreach ($validated['schedule'] as $slot) {
                        $contractor->schedules()->create($slot);
                    }
                });

                return response()->json(['success' => true, 'message' => 'Schedule updated', 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Failed to update schedule'], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            try {
                $contractor = Contractor::findOrFail($id);
                $this->authorize('delete', $contractor);

                $contractor->delete();

                return response()->json(['success' => true, 'message' => 'Contractor deleted', 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Deletion failed'], 500);
            }
        }
}
