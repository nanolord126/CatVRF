<?php

declare(strict_types=1);

namespace App\Domains\Communication\Http\Controllers;

use App\Domains\Communication\DTOs\CreateChannelDto;
use App\Domains\Communication\DTOs\SendMessageDto;
use App\Domains\Communication\Http\Requests\CreateChannelRequest;
use App\Domains\Communication\Http\Requests\SendMessageRequest;
use App\Domains\Communication\Http\Resources\MessageResource;
use App\Domains\Communication\Models\CommunicationChannel;
use App\Domains\Communication\Services\CommunicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * Layer 9: Controller — Communication channels & direct messages.
 *
 * Canon: final class, constructor injection only, no Facades, no DB::,
 * correlation_id in every response, Form Requests for validation.
 */
final class CommunicationController extends Controller
{
    public function __construct(
        private readonly CommunicationService $service,
    ) {}

    /**
     * List channels for the current tenant (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $channels = CommunicationChannel::orderByDesc('created_at')->paginate(20);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data'           => $channels->items(),
            'meta'           => [
                'current_page' => $channels->currentPage(),
                'last_page'    => $channels->lastPage(),
                'total'        => $channels->total(),
            ],
        ]);
    }

    /**
     * Create a new communication channel.
     */
    public function store(CreateChannelRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $dto           = CreateChannelDto::from($request);

        $channel = $this->service->createChannel($dto);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data'           => [
                'id'   => $channel->id,
                'uuid' => $channel->uuid,
                'name' => $channel->name,
                'type' => $channel->type,
            ],
        ], 201);
    }

    /**
     * Show a single channel.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $channel       = CommunicationChannel::findOrFail($id);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data'           => $channel,
        ]);
    }

    /**
     * Update channel settings (reuses CreateChannelRequest — same rules apply).
     */
    public function update(CreateChannelRequest $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $channel       = CommunicationChannel::findOrFail($id);
        $old           = $channel->toArray();

        $channel->update($request->validated());
        $this->service->auditChannelUpdate($channel, $old, $correlationId);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data'           => $channel->fresh(),
        ]);
    }

    /**
     * Disable a channel (soft-delete via status field).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->service->disableChannel($id, $correlationId);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'message'        => 'Channel disabled',
        ]);
    }

    /**
     * Send a direct message via any channel.
     */
    public function sendMessage(SendMessageRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $dto           = SendMessageDto::from($request);

        $message = $this->service->send($dto);

        return (new MessageResource($message))
            ->additional(['correlation_id' => $correlationId])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Inbox — paginated messages for the authenticated user.
     */
    public function inbox(Request $request): AnonymousResourceCollection
    {
        $messages = $this->service->listForRecipient(
            recipientId: (int) $request->user()->id,
            perPage:     (int) $request->query('per_page', 20),
        );

        return MessageResource::collection($messages);
    }

    /**
     * Mark a single message as read.
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->service->markRead($id, $correlationId);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'message'        => 'Message marked as read',
        ]);
    }
}
