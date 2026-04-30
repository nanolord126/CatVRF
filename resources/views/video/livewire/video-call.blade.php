<div x-data="{
    accessToken: @entangle('accessToken'),
    isJoined: @entangle('isJoined'),
    audioEnabled: @entangle('audioEnabled'),
    videoEnabled: @entangle('videoEnabled'),
    screenSharing: @entangle('screenSharing'),
    status: @entangle('status'),
    duration: @entangle('duration'),
    room: @js($room ? json_encode($room) : 'null'),
    localStream: null,
    remoteStreams: {},
}" x-init="
    $listen('room-joined', (data) => {
        initializeLiveKit(data);
    });
    $listen('room-left', () => {
        disconnect();
    });
    $listen('toggle-audio', (data) => {
        toggleAudio(data.enabled);
    });
    $listen('toggle-video', (data) => {
        toggleVideo(data.enabled);
    });
    $listen('toggle-screen-share', (data) => {
        toggleScreenShare(data.enabled);
    });
    $listen('start-timer', () => {
        setInterval(() => {
            @this.tick();
        }, 1000);
    });
" class="h-screen flex flex-col bg-gray-900">
    <!-- Header -->
    <div class="bg-gray-800 border-b border-gray-700 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-3 h-3 rounded-full" :class="{
                'bg-green-500': status === 'connected',
                'bg-yellow-500': status === 'connecting',
                'bg-red-500': status === 'disconnected'
            }"></div>
            <span class="text-white font-medium">Video Call</span>
            @if($room)
                <span class="text-gray-400 text-sm">• {{ $room->type->value }}</span>
            @endif
        </div>
        <div class="flex items-center gap-4">
            <span class="text-gray-300" x-text="formatDuration(duration)"></span>
            @if($requireConsent && !$recordingConsentGiven)
                <button
                    wire:click="giveRecordingConsent"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"
                >
                    Consent to Recording
                </button>
            @endif
        </div>
    </div>

    <!-- Video Grid -->
    <div class="flex-1 p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Local Video -->
        <div class="relative bg-gray-800 rounded-lg overflow-hidden aspect-video">
            <video id="localVideo" autoplay muted playsinline class="w-full h-full object-cover"></video>
            <div class="absolute bottom-2 left-2 bg-black/50 text-white px-2 py-1 rounded text-sm">
                You
            </div>
            @if(!$audioEnabled)
                <div class="absolute top-2 right-2">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"></path>
                    </svg>
                </div>
            @endif
        </div>

        <!-- Remote Videos -->
        <template x-for="(stream, participantId) in remoteStreams" :key="participantId">
            <div class="relative bg-gray-800 rounded-lg overflow-hidden aspect-video">
                <video :id="'remote-' + participantId" autoplay playsinline class="w-full h-full object-cover"></video>
                <div class="absolute bottom-2 left-2 bg-black/50 text-white px-2 py-1 rounded text-sm">
                    <span x-text="stream.name"></span>
                </div>
            </div>
        </template>
    </div>

    <!-- Controls -->
    <div class="bg-gray-800 border-t border-gray-700 px-4 py-4">
        <div class="flex items-center justify-center gap-4">
            <button
                @click="$wire.toggleAudio()"
                class="p-3 rounded-full transition-colors"
                :class="audioEnabled ? 'bg-gray-700 hover:bg-gray-600 text-white' : 'bg-red-600 hover:bg-red-700 text-white'"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <template x-if="audioEnabled">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                    </template>
                    <template x-if="!audioEnabled">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"></path>
                    </template>
                </svg>
            </button>

            <button
                @click="$wire.toggleVideo()"
                class="p-3 rounded-full transition-colors"
                :class="videoEnabled ? 'bg-gray-700 hover:bg-gray-600 text-white' : 'bg-red-600 hover:bg-red-700 text-white'"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <template x-if="videoEnabled">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </template>
                    <template x-if="!videoEnabled">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </template>
                </svg>
            </button>

            <button
                @click="$wire.toggleScreenShare()"
                class="p-3 rounded-full transition-colors"
                :class="screenSharing ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-gray-700 hover:bg-gray-600 text-white'"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </button>

            <button
                @click="$wire.leaveRoom()"
                class="p-3 rounded-full bg-red-600 hover:bg-red-700 text-white transition-colors"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"></path>
                </svg>
            </button>
        </div>
    </div>

    <script>
        function formatDuration(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            if (h > 0) {
                return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
            }
            return `${m}:${s.toString().padStart(2, '0')}`;
        }

        async function initializeLiveKit(data) {
            // LiveKit SDK initialization would go here
            console.log('Initializing LiveKit with:', data);
        }

        function toggleAudio(enabled) {
            // Toggle audio track
        }

        function toggleVideo(enabled) {
            // Toggle video track
        }

        function toggleScreenShare(enabled) {
            // Toggle screen sharing
        }

        function disconnect() {
            // Disconnect from room
        }
    </script>
</div>
