<div x-data="videoCall({ 
    livekitToken: '{{ $livekitToken }}',
    roomId: '{{ $room->id }}',
    roomName: '{{ $room->room_name }}',
    userId: '{{ auth()->id() }}',
    audioEnabled: @js($audioEnabled),
    videoEnabled: @js($videoEnabled)
})" x-init="init()">
    <!-- Connection Status -->
    <div class="mb-4 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">{{ $room->room_name ?? 'Video Call' }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Status: <span x-text="status">{{ $status }}</span>
                </p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Participants: {{ count($participants) }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Duration: <span x-text="formatDuration(duration)"></span>
                </p>
            </div>
        </div>
    </div>

    <!-- Video Container -->
    <div class="relative bg-black rounded-lg overflow-hidden" style="aspect-ratio: 16/9;">
        <!-- Local Video -->
        <video id="localVideo" autoplay muted playsinline class="absolute top-4 right-4 w-48 h-36 object-cover rounded-lg shadow-lg border-2 border-white"></video>
        
        <!-- Remote Videos -->
        <div id="remoteVideos" class="w-full h-full flex items-center justify-center">
            <div class="text-white text-center">
                <p>Waiting for participants...</p>
            </div>
        </div>

        <!-- Connection Indicator -->
        <div x-show="status === 'connecting'" class="absolute inset-0 bg-black bg-opacity-75 flex items-center justify-center">
            <div class="text-white text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto mb-4"></div>
                <p>Connecting...</p>
            </div>
        </div>
    </div>

    <!-- Recording Consent Modal -->
    <div x-show="recordingEnabled && !hasRecordingConsent" 
         x-transition
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold mb-4">Recording Consent</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                This call may be recorded for quality and training purposes. 
                Please provide your consent by signing below.
            </p>
            
            <input type="text" 
                   wire:model="recordingConsentSignature"
                   placeholder="Type your full name as signature"
                   class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 mb-4">
            
            <div class="flex gap-2">
                <button wire:click="grantRecordingConsent" 
                        class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    I Consent
                </button>
                <button @click="hasRecordingConsent = false" 
                        class="flex-1 bg-gray-300 dark:bg-gray-600 px-4 py-2 rounded hover:bg-gray-400">
                    Decline
                </button>
            </div>
        </div>
    </div>

    <!-- Controls -->
    <div class="mt-4 flex items-center justify-center gap-4">
        <!-- Audio Toggle -->
        <button @click="toggleAudio()" 
                :class="audioEnabled ? 'bg-gray-600' : 'bg-red-600'"
                class="p-4 rounded-full text-white hover:opacity-80 transition">
            <x-heroicon-o-microphone x-show="audioEnabled" class="w-6 h-6" />
            <x-heroicon-o-microphone-slash x-show="!audioEnabled" class="w-6 h-6" />
        </button>

        <!-- Video Toggle -->
        <button @click="toggleVideo()" 
                :class="videoEnabled ? 'bg-gray-600' : 'bg-red-600'"
                class="p-4 rounded-full text-white hover:opacity-80 transition">
            <x-heroicon-o-video-camera x-show="videoEnabled" class="w-6 h-6" />
            <x-heroicon-o-video-camera-slash x-show="!videoEnabled" class="w-6 h-6" />
        </button>

        <!-- Screen Share -->
        <button @click="toggleScreenSharing()" 
                :class="screenSharing ? 'bg-blue-600' : 'bg-gray-600'"
                class="p-4 rounded-full text-white hover:opacity-80 transition">
            <x-heroicon-o-computer-desktop class="w-6 h-6" />
        </button>

        <!-- Recording -->
        @if($recordingEnabled)
            <button @click="isRecording ? stopRecording() : startRecording()" 
                    :class="isRecording ? 'bg-red-600 animate-pulse' : 'bg-gray-600'"
                    class="p-4 rounded-full text-white hover:opacity-80 transition">
                <x-heroicon-o-circle-stack class="w-6 h-6" />
            </button>
        @endif

        <!-- Hangup -->
        <button wire:click="hangup" 
                class="p-4 rounded-full bg-red-600 text-white hover:bg-red-700 transition">
            <x-heroicon-o-phone-x-mark class="w-6 h-6" />
        </button>
    </div>

    <!-- Participants List -->
    <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
        <h4 class="font-semibold mb-2">Participants</h4>
        <div class="space-y-2">
            @foreach($participants as $participant)
                <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-700 rounded">
                    <span>{{ $participant['role'] }}: {{ $participant['user_id'] }}</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $participant['status'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    @script
    <script>
        let localStream = null;
        let room = null;
        let duration = 0;
        let durationInterval = null;

        function init() {
            if (this.livekitToken) {
                connectToLiveKit();
            }
            startDurationCounter();
        }

        async function connectToLiveKit() {
            try {
                const LiveKitClient = window.LiveKitClient;
                room = new LiveKitClient.Room();

                await room.connect('wss://your-livekit-server', this.livekitToken);
                
                room.on('participantConnected', handleParticipantConnected);
                room.on('participantDisconnected', handleParticipantDisconnected);
                room.on('trackSubscribed', handleTrackSubscribed);
                
                // Publish local tracks
                localStream = await navigator.mediaDevices.getUserMedia({
                    audio: this.audioEnabled,
                    video: this.videoEnabled
                });
                
                const videoTrack = localStream.getVideoTracks()[0];
                const audioTrack = localStream.getAudioTracks()[0];
                
                if (videoTrack) {
                    await room.localParticipant.publishTrack(videoTrack);
                }
                if (audioTrack) {
                    await room.localParticipant.publishTrack(audioTrack);
                }
                
                this.status = 'connected';
            } catch (error) {
                console.error('Failed to connect to LiveKit:', error);
                this.status = 'error';
            }
        }

        function toggleAudio() {
            if (localStream) {
                const audioTrack = localStream.getAudioTracks()[0];
                if (audioTrack) {
                    audioTrack.enabled = !audioTrack.enabled;
                    this.audioEnabled = audioTrack.enabled;
                }
            }
        }

        function toggleVideo() {
            if (localStream) {
                const videoTrack = localStream.getVideoTracks()[0];
                if (videoTrack) {
                    videoTrack.enabled = !videoTrack.enabled;
                    this.videoEnabled = videoTrack.enabled;
                }
            }
        }

        function toggleScreenSharing() {
            // Implement screen sharing logic
        }

        function handleParticipantConnected(participant) {
            Livewire.dispatch('participantJoined', participant);
        }

        function handleParticipantDisconnected(participant) {
            Livewire.dispatch('participantLeft', participant.identity);
        }

        function handleTrackSubscribed(track, publication, participant) {
            const videoElement = document.getElementById('remoteVideos');
            videoElement.appendChild(track.attach());
        }

        function startDurationCounter() {
            durationInterval = setInterval(() => {
                duration++;
            }, 1000);
        }

        function formatDuration(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
    </script>
    @endscript
</div>
