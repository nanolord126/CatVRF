<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Education;

use App\Http\Controllers\Controller;
use App\Domains\Education\Services\LiveClassService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class LiveClassController extends Controller
{
    public function __construct(
        private LiveClassService $liveClassService,
    ) {}

    public function createSession(int $slotId, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();

        $session = $this->liveClassService->createLiveSession($slotId, $correlationId);

        return response()->json($session)
            ->header('X-Correlation-ID', $correlationId);
    }

    public function joinSession(string $sessionId, Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', 'in:teacher,student'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();
        $userId = (int) $request->input('user_id');
        $role = $request->input('role');

        $result = $this->liveClassService->joinSession($sessionId, $userId, $role, $correlationId);

        return response()->json($result)
            ->header('X-Correlation-ID', $correlationId);
    }

    public function startSession(string $sessionId, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();

        $this->liveClassService->startSession($sessionId, $correlationId);

        return response()->json([
            'message' => 'Session started',
            'session_id' => $sessionId,
        ])
            ->header('X-Correlation-ID', $correlationId);
    }

    public function endSession(string $sessionId, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();

        $this->liveClassService->endSession($sessionId, $correlationId);

        return response()->json([
            'message' => 'Session ended',
            'session_id' => $sessionId,
        ])
            ->header('X-Correlation-ID', $correlationId);
    }

    public function sendChatMessage(string $sessionId, Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'max:1000'],
            'sender_type' => ['required', 'in:teacher,student,ai'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();
        $userId = (int) $request->input('user_id');
        $message = $request->input('message');
        $senderType = $request->input('sender_type');

        $result = $this->liveClassService->sendChatMessage($sessionId, $userId, $message, $senderType, $correlationId);

        return response()->json($result)
            ->header('X-Correlation-ID', $correlationId);
    }

    public function getChatHistory(string $sessionId, Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 50);

        $history = $this->liveClassService->getChatHistory($sessionId, $limit);

        return response()->json([
            'session_id' => $sessionId,
            'messages' => $history,
            'count' => count($history),
        ]);
    }

    public function triggerAIAssistance(string $sessionId, Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();
        $message = $request->input('message');

        $result = $this->liveClassService->triggerAIAssistance($sessionId, $message, $correlationId);

        return response()->json($result)
            ->header('X-Correlation-ID', $correlationId);
    }
}
