<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class EntertainmentEventController extends Controller
{

    public function __construct(private readonly EventService $eventService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $events = EntertainmentEvent::query()
                    ->where('status', '!=', 'cancelled')
                    ->with('venue', 'entertainer', 'schedules')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $events, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $event = EntertainmentEvent::with('venue', 'entertainer', 'schedules', 'reviews')->findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $event, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Event not found', 'correlation_id' => Str::uuid()], 404);
            }
        }

        public function store(): JsonResponse
        {
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $correlationId = Str::uuid()->toString();
                $event = $this->eventService->createEvent(
                    $request->input('venue_id'),
                    $request->input('entertainer_id'),
                    $request->input('name'),
                    $request->input('description'),
                    $request->input('event_type'),
                    new \DateTime($request->input('event_date_start')),
                    new \DateTime($request->input('event_date_end')),
                    $request->input('total_seats'),
                    $request->input('base_price'),
                    $request->input('vip_price'),
                    $correlationId,
                );

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $event, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to create event', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function update(int $id): JsonResponse
        {
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $event = EntertainmentEvent::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($event, $correlationId) {
                    $event->update([
                        'name' => $request->input('name', $event->name),
                        'description' => $request->input('description', $event->description),
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Event updated', ['event_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $event, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function cancel(int $id): JsonResponse
        {
            try {
                $event = EntertainmentEvent::findOrFail($id);
                $correlationId = Str::uuid()->toString();
                $this->eventService->cancelEvent($event, $correlationId);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function getSchedule(int $id): JsonResponse
        {
            try {
                $schedules = EventSchedule::where('entertainment_event_id', $id)
                    ->where('is_cancelled', false)
                    ->orderBy('start_time')
                    ->get();

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $schedules, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function addSchedule(int $eventId): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($eventId, $correlationId) {
                    $schedule = EventSchedule::create([
                        'tenant_id' => tenant()->id,
                        'entertainment_event_id' => $eventId,
                        'show_number' => $request->input('show_number'),
                        'start_time' => $request->input('start_time'),
                        'end_time' => $request->input('end_time'),
                        'total_seats' => $request->input('total_seats'),
                        'available_seats' => $request->input('total_seats'),
                        'ticket_price' => $request->input('ticket_price'),
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Event schedule created', ['event_id' => $eventId, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function updateSchedule(int $scheduleId): JsonResponse
        {
            try {
                $schedule = EventSchedule::findOrFail($scheduleId);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($schedule, $correlationId) {
                    $schedule->update(['correlation_id' => $correlationId]);
                    $this->logger->info('Schedule updated', ['schedule_id' => $schedule->id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $schedule, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function cancelSchedule(int $scheduleId): JsonResponse
        {
            try {
                $schedule = EventSchedule::findOrFail($scheduleId);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($schedule, $correlationId) {
                    $schedule->update(['is_cancelled' => true, 'correlation_id' => $correlationId]);
                    $this->logger->info('Schedule cancelled', ['schedule_id' => $scheduleId, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function getReviews(int $id): JsonResponse
        {
            try {
                $reviews = \App\Domains\EventPlanning\Entertainment\Models\EventReview::where('entertainment_event_id', $id)
                    ->with('reviewer')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function addReview(int $id): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($id, $correlationId) {
                    \App\Domains\EventPlanning\Entertainment\Models\EventReview::create([
                        'tenant_id' => tenant()->id,
                        'entertainment_event_id' => $id,
                        'reviewer_id' => $request->user()?->id,
                        'rating' => $request->input('rating'),
                        'comment' => $request->input('comment'),
                        'verified_purchase' => true,
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Event review created', ['event_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => ['bookings' => $bookings, 'revenue' => $revenue, 'rating' => $event->rating],
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
