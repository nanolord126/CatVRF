<?php

declare(strict_types=1);

namespace Modules\Video\Application\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Video\Application\Services\VideoRoomService;
use Modules\Video\Domain\DTOs\CreateRoomDTO;
use Modules\Video\Domain\DTOs\JoinRoomDTO;
use Modules\Video\Domain\Entities\VideoRoom;
use Modules\Video\Domain\Exceptions\VideoRoomException;
use Modules\Video\Domain\ValueObjects\ParticipantRole;
use Modules\Video\Domain\ValueObjects\RoomType;

final class VideoCall extends Component
{
    public string $roomId;
    public ?string $accessToken = null;
    public ?VideoRoom $room = null;
    public bool $isHost = false;
    public bool $isJoined = false;
    public bool $recordingConsentGiven = false;
    public bool $requireConsent = false;
    public array $participants = [];
    public string $status = 'disconnected';
    public ?string $localStreamId = null;
    public ?string $remoteStreamId = null;
    public bool $audioEnabled = true;
    public bool $videoEnabled = true;
    public bool $screenSharing = false;
    public int $duration = 0;

    public function mount(string $roomId): void
    {
        $this->roomId = $roomId;
        $this->loadRoom();
    }

    public function loadRoom(): void
    {
        $userId = Auth::id();

        if ($userId === null) {
            $this->dispatch('error', 'User not authenticated');
            return;
        }

        try {
            $roomService = app(VideoRoomService::class);
            $this->room = $roomService->getRoom($this->roomId);

            if ($this->room === null) {
                $this->dispatch('error', 'Room not found');
                return;
            }

            $this->isHost = $this->room->hostId === (string) $userId;
            $this->requireConsent = $this->room->type->requiresRecordingConsent();
            $this->status = $this->room->status->value;
            $this->participants = $roomService->getRoomParticipants($this->roomId);

            if ($this->room->status->canJoin()) {
                $this->joinRoom();
            }
        } catch (VideoRoomException $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function joinRoom(): void
    {
        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        try {
            $roomService = app(VideoRoomService::class);

            $dto = new JoinRoomDTO(
                roomId: $this->roomId,
                userId: (string) $userId,
                role: $this->isHost ? ParticipantRole::HOST : ParticipantRole::VIEWER,
            );

            $participant = $roomService->joinRoom($dto);
            $this->accessToken = $roomService->getParticipantAccessToken($this->roomId, (string) $userId);
            $this->isJoined = true;
            $this->status = 'connected';

            $this->dispatch('room-joined', [
                'accessToken' => $this->accessToken,
                'roomName' => $this->room->livekitRoomName,
                'participantIdentity' => $participant->livekitParticipantIdentity,
            ]);

            $this->startDurationTimer();
        } catch (VideoRoomException $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function leaveRoom(): void
    {
        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        try {
            $roomService = app(VideoRoomService::class);
            $roomService->leaveRoom($this->roomId, (string) $userId);

            $this->isJoined = false;
            $this->status = 'disconnected';
            $this->dispatch('room-left');

            if ($this->isHost) {
                $roomService->endRoom($this->roomId);
            }
        } catch (VideoRoomException $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function toggleAudio(): void
    {
        $this->audioEnabled = !$this->audioEnabled;
        $this->dispatch('toggle-audio', ['enabled' => $this->audioEnabled]);
    }

    public function toggleVideo(): void
    {
        $this->videoEnabled = !$this->videoEnabled;
        $this->dispatch('toggle-video', ['enabled' => $this->videoEnabled]);
    }

    public function toggleScreenShare(): void
    {
        $this->screenSharing = !$this->screenSharing;
        $this->dispatch('toggle-screen-share', ['enabled' => $this->screenSharing]);
    }

    public function giveRecordingConsent(): void
    {
        try {
            $roomService = app(VideoRoomService::class);
            $roomService->giveRecordingConsent($this->roomId);
            $this->recordingConsentGiven = true;
            $this->dispatch('consent-given');
        } catch (VideoRoomException $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    #[On('participant-joined')]
    public function onParticipantJoined(array $data): void
    {
        $this->participants[] = $data;
        $this->dispatch('notify', 'Participant joined the call');
    }

    #[On('participant-left')]
    public function onParticipantLeft(string $participantId): void
    {
        $this->participants = array_filter(
            $this->participants,
            fn ($p) => $p['id'] !== $participantId
        );
        $this->dispatch('notify', 'Participant left the call');
    }

    #[On('room-status-changed')]
    public function onRoomStatusChanged(string $status): void
    {
        $this->status = $status;

        if ($status === 'ended') {
            $this->isJoined = false;
            $this->dispatch('room-ended');
        }
    }

    #[On('local-stream-ready')]
    public function onLocalStreamReady(string $streamId): void
    {
        $this->localStreamId = $streamId;
    }

    #[On('remote-stream-ready')]
    public function onRemoteStreamReady(string $streamId): void
    {
        $this->remoteStreamId = $streamId;
    }

    private function startDurationTimer(): void
    {
        $this->dispatch('start-timer');
    }

    #[On('tick')]
    public function onTick(): void
    {
        if ($this->isJoined) {
            $this->duration++;
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('video::livewire.video-call');
    }
}
