<?php

namespace App\Domains\Events\Http\Controllers;

use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\EventService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function __construct(private EventService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Event::where('tenant_id', tenant()->id)->paginate($request->input('per_page', 15))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Event::class);
        return response()->json($this->service->createEvent($request->all()), 201);
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json($event);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);
        $event->update($request->all());
        return response()->json($event);
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);
        $event->delete();
        return response()->json(['message' => 'Event deleted']);
    }
}
