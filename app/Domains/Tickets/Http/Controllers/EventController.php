<?php declare(strict_types=1);

namespace App\Domains\Tickets\Http\Controllers;

use App\Domains\Tickets\Models\{Event, EventCategory};
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class EventController
{
    public function index(): JsonResponse
    {
        try {
            $events = Event::where('status', 'published')
                ->with(['organizer', 'ticketTypes', 'reviews'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $events,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to list events', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
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

            return response()->json([
                'success' => true,
                'data' => $event,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to show event', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }
    }

    public function store(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $this->authorize('create', \App\Domains\Tickets\Models\Event::class);

            $validated = request()->validate([
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

            $correlationId = Str::uuid();

            $event = \App\Domains\Tickets\Models\Event::create([
                'tenant_id' => tenant('id'),
                'organizer_id' => auth()->id(),
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

            \Log::channel('audit')->info('Event created', [
                'event_id' => $event->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $event,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to create event', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create event',
            ], 500);
        }
    }

    public function update(int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $event = \App\Domains\Tickets\Models\Event::findOrFail($id);
            $this->authorize('update', $event);

            $validated = request()->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'status' => 'sometimes|in:draft,published,ongoing,completed,cancelled',
            ]);

            $correlationId = Str::uuid();
            $event->update($validated + ['correlation_id' => $correlationId]);

            \Log::channel('audit')->info('Event updated', [
                'event_id' => $event->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $event,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to update event', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
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

            $correlationId = Str::uuid();
            $event->delete();

            \Log::channel('audit')->info('Event deleted', [
                'event_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to delete event', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete event',
            ], 500);
        }
    }

    public function categories(): JsonResponse
    {
        try {
            $categories = EventCategory::where('is_active', true)->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to fetch categories', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
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

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to fetch analytics', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analytics',
            ], 500);
        }
    }
}
