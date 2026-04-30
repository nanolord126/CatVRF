<?php

declare(strict_types=1);

namespace App\Http\Livewire\Video;

use App\Models\Video\VideoRoom;
use App\Services\Video\VideoRoomService;
use Livewire\Component;

final class LiveStreamComponent extends Component
{
    public VideoRoom $room;
    public ?string $livekitToken = null;
    public bool $isStreaming = false;
    public int $viewerCount = 0;
    public array $chatMessages = [];
    public string $newMessage = '';
    public bool $isHost = false;

    protected VideoRoomService $videoRoomService;

    protected $listeners = [
        'viewerJoined' => 'handleViewerJoined',
        'viewerLeft' => 'handleViewerLeft',
        'chatMessage' => 'handleChatMessage',
        'streamStatusChanged' => 'handleStreamStatusChanged',
    ];

    public function boot(VideoRoomService $videoRoomService): void
    {
        $this->videoRoomService = $videoRoomService;
    }

    public function mount(VideoRoom $room): void
    {
        $this->room = $room;
        
        $userId = auth()->id();
        $this->isHost = $this->room->host_id === $userId && 
                        $this->room->host_type === get_class(auth()->user());

        if ($this->isHost) {
            // Host joins as broadcaster
            $participant = $this->room->participants()
                ->where('user_id', $userId)
                ->where('user_type', get_class(auth()->user()))
                ->first();

            if (!$participant) {
                $participant = $this->videoRoomService->joinRoom(
                    $this->room,
                    $userId,
                    get_class(auth()->user())
                );
            }

            $this->livekitToken = $this->videoRoomService->generateLiveKitToken(
                $this->room,
                $participant,
                120
            );
        }

        $this->viewerCount = $this->room->getActiveParticipantsCount();
        $this->isStreaming = $this->room->status === 'active';
    }

    public function startStream(): void
    {
        if (!$this->isHost) {
            return;
        }

        try {
            $this->videoRoomService->startRoom($this->room);
            $this->isStreaming = true;
            $this->dispatch('streamStarted');
        } catch (\Exception $e) {
            $this->addError('stream', $e->getMessage());
        }
    }

    public function stopStream(): void
    {
        if (!$this->isHost) {
            return;
        }

        try {
            $this->videoRoomService->endRoom($this->room);
            $this->isStreaming = false;
            $this->dispatch('streamEnded');
        } catch (\Exception $e) {
            $this->addError('stream', $e->getMessage());
        }
    }

    public function sendMessage(): void
    {
        if (empty($this->newMessage)) {
            return;
        }

        $message = [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'message' => $this->newMessage,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->chatMessages[] = $message;
        $this->newMessage = '';

        // Broadcast to room
        broadcast(new \App\Events\Video\ChatMessage($this->room, $message));
    }

    public function handleViewerJoined(array $viewerData): void
    {
        $this->viewerCount++;
        $this->dispatch('notify', message: 'New viewer joined');
    }

    public function handleViewerLeft(): void
    {
        $this->viewerCount--;
    }

    public function handleChatMessage(array $message): void
    {
        $this->chatMessages[] = $message;
    }

    public function handleStreamStatusChanged(bool $isStreaming): void
    {
        $this->isStreaming = $isStreaming;
    }

    public function render()
    {
        return view('livewire.video.live-stream-component');
    }
}
