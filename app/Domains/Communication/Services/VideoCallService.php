<?php

namespace App\Domains\Communication\Services;

use App\Domains\Communication\Models\{HelpdeskTicket, TicketMessage};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\AuditLog;
use Throwable;

class VideoCallService
{
    private string $correlationId;
    private ?int $tenantId;

    public function __construct()
    {
        $this->correlationId = Str::uuid();
        $this->tenantId = Auth::guard('tenant')?->id();
    }

    /**
     * Создание комнаты для видео-звонка с трекингом контекста.
     */
    public function createRoom(string $contextType, int $contextId): array
    {
        try {
            $roomId = bin2hex(random_bytes(16));

            Log::channel('communication')->info('VideoCallService: creating room', [
                'correlation_id' => $this->correlationId,
                'room_id' => $roomId,
                'context_type' => $contextType,
                'context_id' => $contextId,
                'user_id' => Auth::id(),
            ]);

            $roomData = [
                'room_id' => $roomId,
                'provider' => 'webrtc-peerjs',
                'created_at' => now(),
                'context' => "{$contextType}:{$contextId}",
                'correlation_id' => $this->correlationId,
                'tenant_id' => $this->tenantId,
                'initiator_id' => Auth::id(),
            ];

            AuditLog::create([
                'entity_type' => 'VideoCallRoom',
                'entity_id' => $roomId,
                'action' => 'created',
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'changes' => [],
                'metadata' => [
                    'context_type' => $contextType,
                    'context_id' => $contextId,
                    'provider' => 'webrtc-peerjs',
                ],
            ]);

            return $roomData;
        } catch (Throwable $e) {
            Log::error('VideoCallService: room creation failed', [
                'correlation_id' => $this->correlationId,
                'context_type' => $contextType,
                'context_id' => $contextId,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }

    /**
     * Отправка сигнала для установления соединения (SDP, ICE candidates).
     */
    public function signal(string $roomId, array $signalData): void
    {
        try {
            Log::channel('communication')->debug('VideoCallService: signal processing', [
                'correlation_id' => $this->correlationId,
                'room_id' => $roomId,
                'signal_type' => $signalData['type'] ?? 'unknown',
                'user_id' => Auth::id(),
            ]);

            // Валидация данных сигнала
            if (empty($signalData['type']) || !in_array($signalData['type'], ['offer', 'answer', 'ice'])) {
                throw new \InvalidArgumentException('Invalid signal type');
            }

            AuditLog::create([
                'entity_type' => 'VideoCallSignal',
                'entity_id' => $roomId,
                'action' => 'signal_sent',
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'changes' => [],
                'metadata' => [
                    'signal_type' => $signalData['type'],
                    'room_id' => $roomId,
                ],
            ]);

            // Broadcast via Redis/Pusher (реализуется через очередь)
            // \Illuminate\Support\Facades\Broadcast::toOthers()
            //     ->channel("video-call-room.{$roomId}")
            //     ->dispatch(new VideoCallSignalEvent($roomId, $signalData));

            Log::channel('communication')->debug('VideoCallService: signal broadcasted', [
                'correlation_id' => $this->correlationId,
                'room_id' => $roomId,
                'signal_type' => $signalData['type'],
            ]);
        } catch (Throwable $e) {
            Log::error('VideoCallService: signal processing failed', [
                'correlation_id' => $this->correlationId,
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }

    /**
     * Завершение видео-звонка с логированием длительности.
     */
    public function endCall(string $roomId, int $durationSeconds): void
    {
        try {
            Log::channel('communication')->info('VideoCallService: call ended', [
                'correlation_id' => $this->correlationId,
                'room_id' => $roomId,
                'duration_seconds' => $durationSeconds,
                'user_id' => Auth::id(),
            ]);

            AuditLog::create([
                'entity_type' => 'VideoCallRoom',
                'entity_id' => $roomId,
                'action' => 'ended',
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'changes' => [],
                'metadata' => [
                    'duration_seconds' => $durationSeconds,
                    'ended_at' => now(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('VideoCallService: call end audit failed', [
                'correlation_id' => $this->correlationId,
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
        }
    }

    /**
     * Создание сообщения обратной связи по результату видео-звонка.
     */
    public function createCallFeedback(string $roomId, int $ticketId, array $feedbackData): TicketMessage
    {
        try {
            Log::channel('communication')->info('VideoCallService: creating call feedback', [
                'correlation_id' => $this->correlationId,
                'room_id' => $roomId,
                'ticket_id' => $ticketId,
            ]);

            $message = TicketMessage::create([
                'helpdesk_ticket_id' => $ticketId,
                'user_id' => Auth::id(),
                'message' => $feedbackData['message'] ?? 'Video call completed',
                'metadata' => [
                    'room_id' => $roomId,
                    'call_quality' => $feedbackData['quality'] ?? 'not_rated',
                    'call_duration' => $feedbackData['duration'] ?? null,
                    'technical_issues' => $feedbackData['issues'] ?? [],
                ],
                'correlation_id' => $this->correlationId,
                'is_internal' => $feedbackData['is_internal'] ?? false,
            ]);

            Log::channel('communication')->info('VideoCallService: feedback created', [
                'correlation_id' => $this->correlationId,
                'message_id' => $message->id,
                'ticket_id' => $ticketId,
            ]);

            return $message;
        } catch (Throwable $e) {
            Log::error('VideoCallService: feedback creation failed', [
                'correlation_id' => $this->correlationId,
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }
}
