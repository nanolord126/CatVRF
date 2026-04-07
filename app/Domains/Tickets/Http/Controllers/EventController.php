<?php declare(strict_types=1);

namespace App\Domains\Tickets\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Domains\Tickets\Models\Event;
use App\Domains\Tickets\Models\EventCategory;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class EventController
{
    public function __construct(
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $events = Event::where('status', 'published')
                    ->with(['organizer', 'ticketTypes', 'reviews'])
                    ->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $events,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to list events', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to list events',
                ], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $event = Event::with(['organizer', 'ticketTypes', 'reviews', 'sales'])
                    ->findOrFail($id);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $event,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to show event', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $this->authorize('create', \App\Domains\Tickets\Models\Event::class);

                $validated = $request->validate([
                    'title' => 'required|string|max:255',
                    'description' => 'required|string',
                    'category' => 'required|string',
                    'starts_at' => 'required|date|after:now',
                    'ends_at' => 'required|date|after:starts_at',
                    'venue_name' => 'required|string',
                    'venue_address' => 'required|string',
                    'total_capacity' => 'required|integer|min:1',
                    'banner_url' => 'nullable|url',
                ]);

                $correlationId = Str::uuid()->toString();

                $event = \App\Domains\Tickets\Models\Event::create([
                    'tenant_id' => tenant()?->id,
                    'organizer_id' => $request->user()?->id,
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'category' => $validated['category'],
                    'status' => 'draft',
                    'starts_at' => $validated['starts_at'],
                    'ends_at' => $validated['ends_at'],
                    'venue_name' => $validated['venue_name'],
                    'venue_address' => $validated['venue_address'],
                    'total_capacity' => $validated['total_capacity'],
                    'banner_url' => $validated['banner_url'] ?? null,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Event created', [
                    'event_id' => $event->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $event,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to create event', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to create event',
                ], 500);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $event = \App\Domains\Tickets\Models\Event::findOrFail($id);
                $this->authorize('update', $event);

                $validated = $request->validate([
                    'title' => 'sometimes|string|max:255',
                    'description' => 'sometimes|string',
                    'status' => 'sometimes|in:draft,published,ongoing,completed,cancelled',
                ]);

                $event->update($validated + ['correlation_id' => $correlationId]);

                $this->logger->info('Event updated', [
                    'event_id' => $event->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $event,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to update event', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to update event',
                ], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            try {
                $event = \App\Domains\Tickets\Models\Event::findOrFail($id);
                $this->authorize('delete', $event);

                $correlationId = Str::uuid()->toString();
                $event->delete();

                $this->logger->info('Event deleted', [
                    'event_id' => $id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Event deleted',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to delete event', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete event',
                ], 500);
            }
        }

        public function categories(): JsonResponse
        {
            try {
                $categories = EventCategory::where('is_active', true)->get();

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $categories,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to fetch categories', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to fetch categories',
                ], 500);
            }
        }

        public function analytics(int $id): JsonResponse
        {
            try {
                $event = \App\Domains\Tickets\Models\Event::findOrFail($id);
                $this->authorize('update', $event);

                $analytics = [
                    'total_tickets_sold' => $event->tickets_sold,
                    'total_capacity' => $event->total_capacity,
                    'remaining_tickets' => $event->total_capacity - $event->tickets_sold,
                    'total_revenue' => $event->sales()->sum('total_amount'),
                    'platform_commission' => $event->sales()->sum('commission_amount'),
                    'organizer_earnings' => $event->sales()->sum('subtotal'),
                    'average_rating' => $event->rating,
                    'total_reviews' => $event->reviews()->count(),
                ];

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $analytics,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to fetch analytics', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to fetch analytics',
                ], 500);
            }
        }
}
