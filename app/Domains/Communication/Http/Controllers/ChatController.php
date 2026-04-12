<?php

declare(strict_types=1);

namespace App\Domains\Communication\Http\Controllers;

use App\Domains\Communication\DTOs\SendChatMessageDto;
use App\Domains\Communication\Http\Requests\SendChatMessageRequest;
use App\Domains\Communication\Http\Resources\ChatMessageResource;
use App\Domains\Communication\Models\ChatRoom;
use App\Domains\Communication\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * Layer 9: Controller — Chat rooms and real-time messages.
 *
 * Canon: final class, DI only, no Facades, correlation_id in every response.
 */
final class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $service,
    ) {}

    /**
     * List all chat rooms for the current tenant (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $rooms = ChatRoom::where('is_active', true)
            ->orderByDesc('created_at')
            ->paginate(20);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data'           => $rooms->items(),
            'meta'           => [
                'current_page' => $rooms->currentPage(),
                'last_page'    => $rooms->lastPage(),
                'total'        => $rooms->total(),
            ],
        ]);
    }

    /**
     * Create a new chat room.
     */
    public function store(Request $request): JsonResponse
    {
        $validated     = $request->validate([
            'type'        => ['required', 'string', 'in:support,b2b_deal,order,direct'],
            'title'       => ['required', 'string', 'max:200'],
            'entity_type' => ['nullable', 'string', 'max:100'],
            'entity_id'   => ['nullable', 'integer'],
        ]);
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $room = $this->service->createRoom(
            tenantId:      (int) tenant()->id,
            type:          $validated['type'],
            title:         $validated['title'],
            correlationId: $correlationId,
            entityType:    $validated['entity_type'] ?? null,
            entityId:      isset($validated['entity_id']) ? (int) $validated['entity_id'] : null,
        );

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data'           => [
                'id'    => $room->id,
                'uuid'  => $room->uuid,
                'type'  => $room->type,
                'title' => $room->title,
            ],
        ], 201);
    }

    /**
     * Paginated message history for a room.
     */
    public function history(Request $request, int $roomId): AnonymousResourceCollection
    {
        $messages = $this->service->getRoomHistory(
            roomId:  $roomId,
            perPage: (int) $request->query('per_page', 50),
        );

        return ChatMessageResource::collection($messages);
    }

    /**
     * Send a message in a chat room (triggers real-time broadcast).
     */
    public function sendMessage(SendChatMessageRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $dto = new SendChatMessageDto(
            tenantId:       (int) tenant()->id,
            roomId:         (int) $request->validated()['room_id'],
            senderId:       (int) $request->user()->id,
            body:           $request->validated()['body'],
            type:           $request->validated()['type'] ?? 'text',
            correlationId:  $correlationId,
            attachmentUrl:  $request->validated()['attachment_url'] ?? null,
            metadata:       $request->validated()['metadata'] ?? [],
        );

        $message = $this->service->sendMessage($dto);

        return (new ChatMessageResource($message))
            ->additional(['correlation_id' => $correlationId])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mark all messages in a room as read for the authenticated user.
     */
    public function markRead(Request $request, int $roomId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->service->markRoomAsRead($roomId, (int) $request->user()->id);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'message'        => 'Room marked as read',
        ]);
    }

    /**
     * Close a chat room.
     */
    public function close(Request $request, int $roomId): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->service->closeRoom($roomId, $correlationId);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'message'        => 'Room closed',
        ]);
    }
}
