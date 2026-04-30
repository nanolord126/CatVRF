<?php

declare(strict_types=1);

namespace App\Http\Livewire\Video;

use App\Models\Video\VideoRoom;
use App\Models\Video\VideoParticipant;
use App\Services\Video\VideoRoomService;
use Livewire\Component;
use Livewire\WithFileUploads;

final class VideoCallComponent extends Component
{
    use WithFileUploads;

    public VideoRoom $room;
    public ?string $livekitToken = null;
    public ?string $localStream = null;
    public bool $audioEnabled = true;
    public bool $videoEnabled = true;
    public bool $screenSharing = false;
    public bool $recordingEnabled = false;
    public ?string $recordingConsentSignature = null;
    public bool $isRecording = false;
    public array $participants = [];
    public string $status = 'connecting';

    protected VideoRoomService $videoRoomService;

    protected $listeners = [
        'participantJoined' => 'handleParticipantJoined',
        'participantLeft' => 'handleParticipantLeft',
        'roomStatusChanged' => 'handleRoomStatusChanged',
        'recordingStatusChanged' => 'handleRecordingStatusChanged',
    ];

    public function boot(VideoRoomService $videoRoomService): void
    {
        $this->videoRoomService = $videoRoomService;
    }

    public function mount(VideoRoom $room): void
    {
        $this->room = $room->load('participants');
        
        // Check if user is already a participant
        $userId = auth()->id();
        $participant = $this->room->participants()
            ->where('user_id', $userId)
            ->where('user_type', get_class(auth()->user()))
            ->first();

        if (!$participant) {
            // Join as new participant
            $participant = $this->videoRoomService->joinRoom(
                $this->room,
                $userId,
                get_class(auth()->user())
            );
        }

        // Generate LiveKit token
        $this->livekitToken = $this->videoRoomService->generateLiveKitToken(
            $this->room,
            $participant,
            60
        );

        $this->participants = $this->room->participants->toArray();
        $this->status = $this->room->status;
        $this->recordingEnabled = $this->room->recording_enabled;
    }

    public function toggleAudio(): void
    {
        $this->audioEnabled = !$this->audioEnabled;
        $this->dispatch('toggleAudio', enabled: $this->audioEnabled);
    }

    public function toggleVideo(): void
    {
        $this->videoEnabled = !$this->videoEnabled;
        $this->dispatch('toggleVideo', enabled: $this->videoEnabled);
    }

    public function toggleScreenSharing(): void
    {
        $this->screenSharing = !$this->screenSharing;
        $this->dispatch('toggleScreenSharing', enabled: $this->screenSharing);
    }

    public function grantRecordingConsent(): void
    {
        if (!$this->recordingConsentSignature) {
            $this->addError('recording', 'Signature is required');
            return;
        }

        try {
            $this->videoRoomService->grantRecordingConsent($this->room, $this->recordingConsentSignature);
            
            if ($this->room->recording_enabled) {
                $this->videoRoomService->enableRecording($this->room);
            }

            $this->dispatch('recordingConsentGranted');
            $this->recordingEnabled = true;
        } catch (\Exception $e) {
            $this->addError('recording', $e->getMessage());
        }
    }

    public function startRecording(): void
    {
        if (!$this->room->hasRecordingConsent()) {
            $this->addError('recording', 'Recording consent must be granted first');
            return;
        }

        $this->dispatch('startRecording');
        $this->isRecording = true;
    }

    public function stopRecording(): void
    {
        $this->dispatch('stopRecording');
        $this->isRecording = false;
    }

    public function hangup(): void
    {
        $userId = auth()->id();
        $participant = $this->room->participants()
            ->where('user_id', $userId)
            ->where('user_type', get_class(auth()->user()))
            ->first();

        if ($participant) {
            $this->videoRoomService->leaveRoom($participant);
        }

        return redirect()->route('video.rooms.index');
    }

    public function handleParticipantJoined(array $participantData): void
    {
        $this->participants[] = $participantData;
        $this->dispatch('notify', message: 'New participant joined the call');
    }

    public function handleParticipantLeft(string $participantId): void
    {
        $this->participants = array_filter($this->participants, fn ($p) => $p['id'] !== $participantId);
        $this->dispatch('notify', message: 'Participant left the call');
    }

    public function handleRoomStatusChanged(string $status): void
    {
        $this->status = $status;
        $this->room->refresh();
    }

    public function handleRecordingStatusChanged(bool $isRecording): void
    {
        $this->isRecording = $isRecording;
    }

    public function render()
    {
        return view('livewire.video.video-call-component');
    }
}
