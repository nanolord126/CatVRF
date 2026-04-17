<?php declare(strict_types=1);

namespace App\Domains\Education\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

final readonly class LiveClassService
{
    private const CACHE_TTL = 3600;
    private const CHAT_HISTORY_LIMIT = 50;

    public function __construct(
        private AuditService $audit,
        private FraudControlService $fraud,
    ) {}

    public function createLiveSession(int $slotId, string $correlationId): array
    {
        $slot = DB::table('education_slots')->where('id', $slotId)->first();

        if ($slot === null) {
            throw new \DomainException('Slot not found');
        }

        $sessionId = (string) Str::uuid();
        $meetingId = $this->generateMeetingId();
        $teacherToken = $this->generateWebRTCToken($slot->teacher_id, 'teacher', $sessionId);
        $studentToken = $this->generateWebRTCToken(0, 'student', $sessionId);

        return DB::transaction(function () use ($slot, $sessionId, $meetingId, $teacherToken, $studentToken, $correlationId) {
            $liveSession = DB::table('education_live_sessions')->insertGetId([
                'id' => $sessionId,
                'tenant_id' => $slot->tenant_id,
                'business_group_id' => $slot->business_group_id,
                'slot_id' => $slotId,
                'teacher_id' => $slot->teacher_id,
                'meeting_id' => $meetingId,
                'teacher_token' => $teacherToken,
                'status' => 'scheduled',
                'started_at' => null,
                'ended_at' => null,
                'participant_count' => 0,
                'correlation_id' => $correlationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->audit->record('education_live_session_created', 'LiveSession', $liveSession, [], [
                'correlation_id' => $correlationId,
                'session_id' => $sessionId,
                'slot_id' => $slotId,
                'meeting_id' => $meetingId,
            ], $correlationId);

            return [
                'session_id' => $sessionId,
                'meeting_id' => $meetingId,
                'teacher_token' => $teacherToken,
                'student_token' => $studentToken,
                'status' => 'scheduled',
            ];
        });
    }

    public function joinSession(string $sessionId, int $userId, string $role, string $correlationId): array
    {
        $session = DB::table('education_live_sessions')->where('id', $sessionId)->first();

        if ($session === null) {
            throw new \DomainException('Session not found');
        }

        if ($session->status !== 'scheduled' && $session->status !== 'active') {
            throw new \DomainException('Session is not available for joining');
        }

        $token = $this->generateWebRTCToken($userId, $role, $sessionId);

        DB::table('education_live_participants')->insert([
            'id' => (string) Str::uuid(),
            'session_id' => $sessionId,
            'user_id' => $userId,
            'role' => $role,
            'token' => $token,
            'joined_at' => now(),
            'left_at' => null,
            'correlation_id' => $correlationId,
        ]);

        DB::table('education_live_sessions')
            ->where('id', $sessionId)
            ->increment('participant_count');

        return [
            'session_id' => $sessionId,
            'meeting_id' => $session->meeting_id,
            'token' => $token,
            'role' => $role,
        ];
    }

    public function startSession(string $sessionId, string $correlationId): void
    {
        DB::transaction(function () use ($sessionId, $correlationId) {
            DB::table('education_live_sessions')
                ->where('id', $sessionId)
                ->update([
                    'status' => 'active',
                    'started_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->audit->record('education_live_session_started', 'LiveSession', $sessionId, [], [
                'correlation_id' => $correlationId,
                'session_id' => $sessionId,
            ], $correlationId);
        });
    }

    public function endSession(string $sessionId, string $correlationId): void
    {
        DB::transaction(function () use ($sessionId, $correlationId) {
            DB::table('education_live_sessions')
                ->where('id', $sessionId)
                ->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->audit->record('education_live_session_ended', 'LiveSession', $sessionId, [], [
                'correlation_id' => $correlationId,
                'session_id' => $sessionId,
            ], $correlationId);
        });
    }

    public function sendChatMessage(string $sessionId, int $userId, string $message, string $senderType, string $correlationId): array
    {
        $messageId = (string) Str::uuid();
        $isAIResponse = $senderType === 'ai';

        if ($isAIResponse) {
            $message = $this->generateAIResponse($sessionId, $message);
        }

        DB::table('education_live_chat_messages')->insert([
            'id' => $messageId,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'sender_type' => $senderType,
            'message' => $message,
            'created_at' => now(),
        ]);

        $chatKey = "education:live:chat:{$sessionId}";
        Redis::lpush($chatKey, json_encode([
            'message_id' => $messageId,
            'user_id' => $userId,
            'sender_type' => $senderType,
            'message' => $message,
            'created_at' => now()->toIso8601String(),
        ]));
        Redis::ltrim($chatKey, 0, self::CHAT_HISTORY_LIMIT - 1);
        Redis::expire($chatKey, self::CACHE_TTL);

        return [
            'message_id' => $messageId,
            'sender_type' => $senderType,
            'message' => $message,
        ];
    }

    public function getChatHistory(string $sessionId, int $limit = 50): array
    {
        $chatKey = "education:live:chat:{$sessionId}";
        $messages = Redis::lrange($chatKey, 0, $limit - 1);

        if (empty($messages)) {
            $messages = DB::table('education_live_chat_messages')
                ->where('session_id', $sessionId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(fn ($msg) => json_encode([
                    'message_id' => $msg->id,
                    'user_id' => $msg->user_id,
                    'sender_type' => $msg->sender_type,
                    'message' => $msg->message,
                    'created_at' => $msg->created_at->toIso8601String(),
                ]))
                ->toArray();
        }

        return array_map(fn ($msg) => json_decode($msg, true), $messages);
    }

    public function triggerAIAssistance(string $sessionId, string $userMessage, string $correlationId): array
    {
        $this->fraud->check([
            'operation_type' => 'ai_assistance_trigger',
            'session_id' => $sessionId,
            'correlation_id' => $correlationId,
        ]);

        $aiResponse = $this->generateAIResponse($sessionId, $userMessage);

        return $this->sendChatMessage($sessionId, 0, $aiResponse, 'ai', $correlationId);
    }

    private function generateWebRTCToken(int $userId, string $role, string $sessionId): string
    {
        $payload = [
            'user_id' => $userId,
            'role' => $role,
            'session_id' => $sessionId,
            'exp' => now()->addHours(4)->timestamp,
        ];

        return base64_encode(json_encode($payload));
    }

    private function generateMeetingId(): string
    {
        return strtoupper(Str::random(3) . '-' . Str::random(4) . '-' . Str::random(3));
    }

    private function generateAIResponse(string $sessionId, string $userMessage): string
    {
        $context = $this->getSessionContext($sessionId);
        
        $responses = [
            'Based on the current topic, I recommend focusing on...',
            'That\'s a great question! Let me explain...',
            'To better understand this concept, consider...',
            'Here\'s a practical example to illustrate...',
        ];

        $responseIndex = crc32($userMessage) % count($responses);
        
        return $responses[$responseIndex] . ' ' . $context;
    }

    private function getSessionContext(string $sessionId): string
    {
        $session = DB::table('education_live_sessions')
            ->join('education_slots', 'education_live_sessions.slot_id', '=', 'education_slots.id')
            ->where('education_live_sessions.id', $sessionId)
            ->select('education_slots.title', 'education_slots.course_id')
            ->first();

        if ($session === null) {
            return '';
        }

        $course = DB::table('courses')->where('id', $session->course_id)->first();
        
        return $course ? "in the context of {$course->title}" : '';
    }
}
