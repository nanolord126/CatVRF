<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TrainerController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function index(): JsonResponse
        {
            try {
                $trainers = Trainer::where('is_verified', true)
                    ->where('is_active', true)
                    ->with(['gym', 'fitnessClasses'])
                    ->paginate(20);

                return response()->json(['success' => true, 'data' => $trainers, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $trainer = Trainer::with(['gym', 'fitnessClasses', 'schedules'])->findOrFail($id);

                return response()->json(['success' => true, 'data' => $trainer, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
            }
        }

        public function myClasses(): JsonResponse
        {
            try {
                $trainer = auth()->user()->trainer;
                $classes = $trainer->fitnessClasses()->paginate(20);

                return response()->json(['success' => true, 'data' => $classes, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function register(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                request()->validate([
                    'gym_id' => 'required|exists:gyms,id',
                    'full_name' => 'required|string',
                    'experience_years' => 'required|integer',
                    'hourly_rate' => 'required|numeric',
                ]);

                $trainer = DB::transaction(function () use ($correlationId) {
                    return Trainer::create([
                        'tenant_id' => tenant('id'),
                        'gym_id' => request('gym_id'),
                        'user_id' => auth()->id(),
                        'full_name' => request('full_name'),
                        'bio' => request('bio'),
                        'specializations' => request('specializations', []),
                        'experience_years' => request('experience_years'),
                        'hourly_rate' => request('hourly_rate'),
                        'is_active' => true,
                        'correlation_id' => $correlationId,
                    ]);
                });

                Log::channel('audit')->info('Trainer registered', ['trainer_id' => $trainer->id, 'user_id' => auth()->id(), 'correlation_id' => $correlationId]);

                return response()->json(['success' => true, 'data' => $trainer, 'correlation_id' => $correlationId], 201);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to register trainer', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function myProfile(): JsonResponse
        {
            try {
                $trainer = Trainer::where('user_id', auth()->id())->first();

                return response()->json(['success' => true, 'data' => $trainer, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
            }
        }

        public function updateProfile(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $trainer = Trainer::where('user_id', auth()->id())->firstOrFail();
                $trainer->update(array_merge(request()->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), ['correlation_id' => $correlationId]));

                Log::channel('audit')->info('Trainer profile updated', ['trainer_id' => $trainer->id, 'correlation_id' => $correlationId]);

                return response()->json(['success' => true, 'data' => $trainer, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function getSchedule(): JsonResponse
        {
            try {
                $trainer = Trainer::where('user_id', auth()->id())->firstOrFail();
                $schedule = $trainer->schedules;

                return response()->json(['success' => true, 'data' => $schedule, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
            }
        }

        public function updateSchedule(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $trainer = Trainer::where('user_id', auth()->id())->firstOrFail();

                DB::transaction(function () use ($trainer, $correlationId) {
                    $trainer->schedules()->delete();

                    foreach (request('schedule', []) as $slot) {
                        TrainerSchedule::create([
                            'tenant_id' => tenant('id'),
                            'trainer_id' => $trainer->id,
                            'day_of_week' => $slot['day_of_week'],
                            'start_time' => $slot['start_time'],
                            'end_time' => $slot['end_time'],
                            'is_available' => $slot['is_available'] ?? true,
                            'correlation_id' => $correlationId,
                        ]);
                    }
                });

                Log::channel('audit')->info('Trainer schedule updated', ['trainer_id' => $trainer->id, 'correlation_id' => $correlationId]);

                return response()->json(['success' => true, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function myEarnings(): JsonResponse
        {
            try {
                $trainer = Trainer::where('user_id', auth()->id())->firstOrFail();

                return response()->json(['success' => true, 'data' => [], 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
            }
        }
}
