<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Http\Controllers;

use App\Domains\Entertainment\Models\Entertainer;
use App\Domains\Entertainment\Models\PerformerSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EntertainerController
{
    public function index(): JsonResponse
    {
        try {
            $entertainers = Entertainer::query()
                ->where('is_verified', true)
                ->where('is_active', true)
                ->with('venue', 'entertainmentEvents')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $entertainers, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $entertainer = Entertainer::with('venue', 'entertainmentEvents', 'schedules')->findOrFail($id);
            return response()->json(['success' => true, 'data' => $entertainer, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Entertainer not found', 'correlation_id' => Str::uuid()], 404);
        }
    }

    public function getEvents(int $id): JsonResponse
    {
        try {
            $events = \App\Domains\Entertainment\Models\Entertainment$this->event->where('entertainer_id', $id)
                ->where('status', '!=', 'cancelled')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $events, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function register(): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($correlationId) {
                $entertainer = Entertainer::create([
                    'tenant_id' => tenant('id'),
                    'user_id' => auth()->id(),
                    'venue_id' => request('venue_id'),
                    'full_name' => request('full_name'),
                    'bio' => request('bio'),
                    'specializations' => request('specializations'),
                    'experience' => request('experience'),
                    'hourly_rate' => request('hourly_rate'),
                    'is_active' => true,
                    'correlation_id' => $correlationId,
                ]);

                $this->log->channel('audit')->info('Entertainer registered', [
                    'entertainer_id' => $entertainer->id,
                    'user_id' => auth()->id(),
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
        }
    }

    public function myProfile(): JsonResponse
    {
        try {
            $entertainer = Entertainer::where('user_id', auth()->id())->first();
            return response()->json([
                'success' => true,
                'data' => $entertainer,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function updateProfile(): JsonResponse
    {
        try {
            $entertainer = Entertainer::where('user_id', auth()->id())->firstOrFail();
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($entertainer, $correlationId) {
                $entertainer->update([
                    'full_name' => request('full_name', $entertainer->full_name),
                    'bio' => request('bio', $entertainer->bio),
                    'correlation_id' => $correlationId,
                ]);

                $this->log->channel('audit')->info('Entertainer profile updated', [
                    'entertainer_id' => $entertainer->id,
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json(['success' => true, 'data' => $entertainer, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function getSchedule(): JsonResponse
    {
        try {
            $entertainer = Entertainer::where('user_id', auth()->id())->firstOrFail();
            $schedules = $entertainer->schedules()->get();

            return response()->json(['success' => true, 'data' => $schedules, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function updateSchedule(): JsonResponse
    {
        try {
            $entertainer = Entertainer::where('user_id', auth()->id())->firstOrFail();
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($entertainer, $correlationId) {
                $entertainer->schedules()->delete();

                foreach (request('schedules', []) as $schedule) {
                    PerformerSchedule::create([
                        'tenant_id' => tenant('id'),
                        'entertainer_id' => $entertainer->id,
                        'day_of_week' => $schedule['day_of_week'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'is_available' => $schedule['is_available'] ?? true,
                        'correlation_id' => $correlationId,
                    ]);
                }

                $this->log->channel('audit')->info('Entertainer schedule updated', [
                    'entertainer_id' => $entertainer->id,
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function myEarnings(): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => [], 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function analytics(int $entertainerId): JsonResponse
    {
        try {
            $entertainer = Entertainer::findOrFail($entertainerId);
            $eventCount = $entertainer->entertainmentEvents()->count();
            $totalEarnings = 0; 

            return response()->json([
                'success' => true,
                'data' => ['events' => $eventCount, 'earnings' => $totalEarnings, 'rating' => $entertainer->rating],
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }
}
