<?php

namespace App\Domains\Communication\Livewire;

use Livewire\Component;
use App\Domains\Communication\Services\VideoCallService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;
use Throwable;

class VideoCallRoom extends Component
{
    public string $roomId = '';
    public string $contextType = '';
    public int $contextId = 0;
    public bool $isCameraOn = true;
    public bool $isMicOn = true;
    
    private string $correlationId = '';
    private ?int $tenantId = null;
    private int $callStartTime = 0;

    public function mount(string $room, string $type, int $id): void
    {
        try {
            $this->correlationId = Str::uuid();
            $this->tenantId = Auth::guard('tenant')?->id();
            $this->roomId = $room;
            $this->contextType = $type;
            $this->contextId = $id;
            $this->callStartTime = time();

            Log::channel('communication')->info('VideoCallRoom: mounted', [
                'correlation_id' => $this->correlationId,
                'room_id' => $this->roomId,
                'context_type' => $this->contextType,
                'context_id' => $this->contextId,
                'user_id' => Auth::id(),
            ]);

            AuditLog::create([
                'entity_type' => 'VideoCallRoom',
                'entity_id' => $this->roomId,
                'action' => 'mounted',
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'changes' => [],
                'metadata' => [
                    'context_type' => $this->contextType,
                    'context_id' => $this->contextId,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('VideoCallRoom: mount failed', [
                'correlation_id' => $this->correlationId,
                'room' => $room,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            session()->flash('error', 'Ошибка при загрузке видео-комнаты');
        }
    }

    public function toggleCamera(): void
    {
        try {
            $this->isCameraOn = !$this->isCameraOn;

            Log::channel('communication')->debug('VideoCallRoom: camera toggled', [
                'correlation_id' => $this->correlationId,
                'room_id' => $this->roomId,
                'camera_on' => $this->isCameraOn,
                'user_id' => Auth::id(),
            ]);

            AuditLog::create([
                'entity_type' => 'VideoCallRoom',
                'entity_id' => $this->roomId,
                'action' => 'camera_toggled',
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'changes' => [],
                'metadata' => [
                    'camera_enabled' => $this->isCameraOn,
                    'toggled_at' => now(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('VideoCallRoom: camera toggle failed', [
                'correlation_id' => $this->correlationId,
                'room_id' => $this->roomId,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
        }
    }

    public function toggleMic(): void
    {
        try {
            $this->isMicOn = !$this->isMicOn;

            Log::channel('communication')->debug('VideoCallRoom: microphone toggled', [
                'correlation_id' => $this->correlationId,
                'room_id' => $this->roomId,
                'mic_on' => $this->isMicOn,
                'user_id' => Auth::id(),
            ]);

            AuditLog::create([
                'entity_type' => 'VideoCallRoom',
                'entity_id' => $this->roomId,
                'action' => 'microphone_toggled',
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'changes' => [],
                'metadata' => [
                    'microphone_enabled' => $this->isMicOn,
                    'toggled_at' => now(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('VideoCallRoom: microphone toggle failed', [
                'correlation_id' => $this->correlationId,
                'room_id' => $this->roomId,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
        }
    }

    public function endCall(): void
    {
        try {
            $callDuration = time() - $this->callStartTime;

            Log::channel('communication')->info('VideoCallRoom: call ended', [
                'correlation_id' => $this->correlationId,
                'room_id' => $this->roomId,
                'duration_seconds' => $callDuration,
                'user_id' => Auth::id(),
            ]);

            AuditLog::create([
                'entity_type' => 'VideoCallRoom',
                'entity_id' => $this->roomId,
                'action' => 'call_ended',
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'changes' => [],
                'metadata' => [
                    'duration_seconds' => $callDuration,
                    'context_type' => $this->contextType,
                    'context_id' => $this->contextId,
                ],
            ]);

            // Trigger service to handle end-of-call logic
            (new VideoCallService())->endCall($this->roomId, $callDuration);

            $this->redirect(route('communication.tickets.show', $this->contextId));
        } catch (Throwable $e) {
            Log::error('VideoCallRoom: call end failed', [
                'correlation_id' => $this->correlationId,
                'room_id' => $this->roomId,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            session()->flash('error', 'Ошибка при завершении вызова');
        }
    }

    public function render()
    {
        return view('livewire.communication.video-call-room');
    }
}
