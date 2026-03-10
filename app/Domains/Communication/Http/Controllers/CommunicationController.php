<?php

namespace App\Domains\Communication\Http\Controllers;

use App\Domains\Communication\Models\Message;
use App\Domains\Communication\Services\CommunicationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommunicationController extends Controller
{
    public function __construct(private CommunicationService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Message::where('recipient_id', auth()->id())
                ->orWhere('user_id', auth()->id())
                ->paginate($request->input('per_page', 15))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Message::class);
        return response()->json($this->service->sendMessage($request->all()), 201);
    }

    public function show(Message $message): JsonResponse
    {
        $this->authorize('view', $message);
        return response()->json($message);
    }

    public function update(Request $request, Message $message): JsonResponse
    {
        $this->authorize('update', $message);
        return response()->json($this->service->markAsRead($message));
    }

    public function destroy(Message $message): JsonResponse
    {
        $this->authorize('delete', $message);
        $this->service->deleteMessage($message);
        return response()->json(['message' => 'Message deleted']);
    }
}
