<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EntertainmentEventController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly EventService $eventService,
            private readonly FraudControlService $fraudControlService,) {}

        public function index(): JsonResponse
        {
            try {
                $events = EntertainmentEvent::query()
                    ->where('status', '!=', 'cancelled')
                    ->with('venue', 'entertainer', 'schedules')
                    ->paginate(20);

                return response()->json(['success' => true, 'data' => $events, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $event = EntertainmentEvent::with('venue', 'entertainer', 'schedules', 'reviews')->findOrFail($id);
                return response()->json(['success' => true, 'data' => $event, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Event not found', 'correlation_id' => Str::uuid()], 404);
            }
        }

        public function store(): JsonResponse
        {
            $fraudResult = $this->fraudControlService->check(
                auth()->id() ?? 0,
                'operation',
                0,
                request()->ip(),
                request()->header('X-Device-Fingerprint'),
                $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('Operation blocked by fraud control', [
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
                $correlationId = Str::uuid()->toString();
                $event = $this->eventService->createEvent(
                    request('venue_id'),
                    request('entertainer_id'),
                    request('name'),
                    request('description'),
                    request('event_type'),
                    new \DateTime(request('event_date_start')),
                    new \DateTime(request('event_date_end')),
                    request('total_seats'),
                    request('base_price'),
                    request('vip_price'),
                    $correlationId,
                );

                return response()->json(['success' => true, 'data' => $event, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to create event', ['error' => $e->getMessage()]);
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function update(int $id): JsonResponse
        {
            $fraudResult = $this->fraudControlService->check(
                auth()->id() ?? 0,
                'operation',
                0,
                request()->ip(),
                request()->header('X-Device-Fingerprint'),
                $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('Operation blocked by fraud control', [
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
                $event = EntertainmentEvent::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                DB::transaction(function () use ($event, $correlationId) {
                    $event->update([
                        'name' => request('name', $event->name),
                        'description' => request('description', $event->description),
                        'correlation_id' => $correlationId,
                    ]);

                    Log::channel('audit')->info('Event updated', ['event_id' => $id, 'correlation_id' => $correlationId]);
                });

                return response()->json(['success' => true, 'data' => $event, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function cancel(int $id): JsonResponse
        {
            try {
                $event = EntertainmentEvent::findOrFail($id);
                $correlationId = Str::uuid()->toString();
                $this->eventService->cancelEvent($event, $correlationId);

                return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function getSchedule(int $id): JsonResponse
        {
            try {
                $schedules = EventSchedule::where('entertainment_event_id', $id)
                    ->where('is_cancelled', false)
                    ->orderBy('start_time')
                    ->get();

                return response()->json(['success' => true, 'data' => $schedules, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function addSchedule(int $eventId): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                DB::transaction(function () use ($eventId, $correlationId) {
                    $schedule = EventSchedule::create([
                        'tenant_id' => tenant('id'),
                        'entertainment_event_id' => $eventId,
                        'show_number' => request('show_number'),
                        'start_time' => request('start_time'),
                        'end_time' => request('end_time'),
                        'total_seats' => request('total_seats'),
                        'available_seats' => request('total_seats'),
                        'ticket_price' => request('ticket_price'),
                        'correlation_id' => $correlationId,
                    ]);

                    Log::channel('audit')->info('Event schedule created', ['event_id' => $eventId, 'correlation_id' => $correlationId]);
                });

                return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function updateSchedule(int $scheduleId): JsonResponse
        {
            try {
                $schedule = EventSchedule::findOrFail($scheduleId);
                $correlationId = Str::uuid()->toString();

                DB::transaction(function () use ($schedule, $correlationId) {
                    $schedule->update(['correlation_id' => $correlationId]);
                    Log::channel('audit')->info('Schedule updated', ['schedule_id' => $schedule->id, 'correlation_id' => $correlationId]);
                });

                return response()->json(['success' => true, 'data' => $schedule, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function cancelSchedule(int $scheduleId): JsonResponse
        {
            try {
                $schedule = EventSchedule::findOrFail($scheduleId);
                $correlationId = Str::uuid()->toString();

                DB::transaction(function () use ($schedule, $correlationId) {
                    $schedule->update(['is_cancelled' => true, 'correlation_id' => $correlationId]);
                    Log::channel('audit')->info('Schedule cancelled', ['schedule_id' => $scheduleId, 'correlation_id' => $correlationId]);
                });

                return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function getReviews(int $id): JsonResponse
        {
            try {
                $reviews = \App\Domains\EventPlanning\Entertainment\Models\EventReview::where('entertainment_event_id', $id)
                    ->with('reviewer')
                    ->paginate(20);

                return response()->json(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function addReview(int $id): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                DB::transaction(function () use ($id, $correlationId) {
                    \App\Domains\EventPlanning\Entertainment\Models\EventReview::create([
                        'tenant_id' => tenant('id'),
                        'entertainment_event_id' => $id,
                        'reviewer_id' => auth()->id(),
                        'rating' => request('rating'),
                        'comment' => request('comment'),
                        'verified_purchase' => true,
                        'correlation_id' => $correlationId,
                    ]);

                    Log::channel('audit')->info('Event review created', ['event_id' => $id, 'correlation_id' => $correlationId]);
                });

                return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function analytics(int $eventId): JsonResponse
        {
            try {
                $event = EntertainmentEvent::findOrFail($eventId);
                $bookings = $event->schedules()->sum(function ($schedule) {
                    return $schedule->bookings()->count();
                });
                $revenue = $event->schedules()->sum(function ($schedule) {
                    return $schedule->bookings()->sum('total_price');
                });

                return response()->json([
                    'success' => true,
                    'data' => ['bookings' => $bookings, 'revenue' => $revenue, 'rating' => $event->rating],
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
