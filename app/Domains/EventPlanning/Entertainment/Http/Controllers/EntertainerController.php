<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class EntertainerController extends Controller
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}


    public function index(): JsonResponse
        {
            try {
                $entertainers = Entertainer::query()
                    ->where('is_verified', true)
                    ->where('is_active', true)
                    ->with('venue', 'entertainmentEvents')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $entertainers, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $entertainer = Entertainer::with('venue', 'entertainmentEvents', 'schedules')->findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $entertainer, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Entertainer not found', 'correlation_id' => Str::uuid()], 404);
            }
        }

        public function getEvents(int $id): JsonResponse
        {
            try {
                $events = \App\Domains\EventPlanning\Entertainment\Models\EntertainmentEvent::where('entertainer_id', $id)
                    ->where('status', '!=', 'cancelled')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $events, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function register(): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($correlationId) {
                    $entertainer = Entertainer::create([
                        'tenant_id' => tenant()->id,
                        'user_id' => $request->user()?->id,
                        'venue_id' => $request->input('venue_id'),
                        'full_name' => $request->input('full_name'),
                        'bio' => $request->input('bio'),
                        'specializations' => $request->input('specializations'),
                        'experience' => $request->input('experience'),
                        'hourly_rate' => $request->input('hourly_rate'),
                        'is_active' => true,
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Entertainer registered', [
                        'entertainer_id' => $entertainer->id,
                        'user_id' => $request->user()?->id,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function myProfile(): JsonResponse
        {
            try {
                $entertainer = Entertainer::where('user_id', $request->user()?->id)->first();
                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $entertainer,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function updateProfile(): JsonResponse
        {
            try {
                $entertainer = Entertainer::where('user_id', $request->user()?->id)->firstOrFail();
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($entertainer, $correlationId) {
                    $entertainer->update([
                        'full_name' => $request->input('full_name', $entertainer->full_name),
                        'bio' => $request->input('bio', $entertainer->bio),
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Entertainer profile updated', [
                        'entertainer_id' => $entertainer->id,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $entertainer, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function getSchedule(): JsonResponse
        {
            try {
                $entertainer = Entertainer::where('user_id', $request->user()?->id)->firstOrFail();
                $schedules = $entertainer->schedules()->get();

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $schedules, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function updateSchedule(): JsonResponse
        {
            try {
                $entertainer = Entertainer::where('user_id', $request->user()?->id)->firstOrFail();
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($entertainer, $correlationId) {
                    $entertainer->schedules()->delete();

                    foreach ($request->input('schedules', []) as $schedule) {
                        PerformerSchedule::create([
                            'tenant_id' => tenant()->id,
                            'entertainer_id' => $entertainer->id,
                            'day_of_week' => $schedule['day_of_week'],
                            'start_time' => $schedule['start_time'],
                            'end_time' => $schedule['end_time'],
                            'is_available' => $schedule['is_available'] ?? true,
                            'correlation_id' => $correlationId,
                        ]);
                    }

                    $this->logger->info('Entertainer schedule updated', [
                        'entertainer_id' => $entertainer->id,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function myEarnings(): JsonResponse
        {
            try {
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => [], 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function analytics(int $entertainerId): JsonResponse
        {
            try {
                $entertainer = Entertainer::findOrFail($entertainerId);
                $eventCount = $entertainer->entertainmentEvents()->count();
                $totalEarnings = 0;

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => ['events' => $eventCount, 'earnings' => $totalEarnings, 'rating' => $entertainer->rating],
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
