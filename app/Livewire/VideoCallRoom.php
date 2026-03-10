<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\VideoCall;
use App\Services\VideoCallService;

class VideoCallRoom extends Component
{
    public $roomId;
    public $turnConfig;

    public function mount($roomId, VideoCallService $service) {
        $this->roomId = $roomId;
        $this->turnConfig = $service->getTurnConfig();
    }

    public function render() {
        return view('livewire.webrtc.room')->layout('layouts.app');
    }
}
