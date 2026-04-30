<div x-data="liveStream({ 
    roomId: '{{ $room->id }}',
    isHost: @js($isHost),
    livekitToken: '{{ $livekitToken }}'
    })" x-init="init()">
    <!-- Stream Header -->
    <div class="mb-4 p-4 bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold">{{ $room->room_name ?? 'Live Stream' }}</h3>
                <p class="text-sm opacity-90">
                    <span x-show="isStreaming" class="flex items-center gap-1">
                        <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                        LIVE
                    </span>
                    <span x-show="!isStreaming">OFFLINE</span>
                </p>
            </div>
            <div class="text-right">
                <p class="text-sm">
                    <x-heroicon-o-user class="w-4 h-4 inline" />
                    <span x-text="viewerCount">{{ $viewerCount }}</span> viewers
                </p>
            </div>
        </div>
    </div>

    <!-- Video Player -->
    <div class="relative bg-black rounded-lg overflow-hidden" style="aspect-ratio: 16/9;">
        <!-- Host Video (if host) -->
        @if($isHost)
            <video id="hostVideo" autoplay muted playsinline class="w-full h-full object-contain"></video>
        @else
            <!-- Viewer Video -->
            <video id="viewerVideo" autoplay playsinline class="w-full h-full object-contain"></video>
        @endif

        <!-- Offline Placeholder -->
        <div x-show="!isStreaming" class="absolute inset-0 bg-gray-900 flex items-center justify-center">
            <div class="text-white text-center">
                <x-heroicon-o-broadcast-off class="w-16 h-16 mx-auto mb-4 opacity-50" />
                <p class="text-xl font-semibold">Stream is offline</p>
                <p class="text-sm text-gray-400 mt-2">Check back later</p>
            </div>
        </div>

        <!-- Live Badge -->
        <div x-show="isStreaming" class="absolute top-4 left-4 bg-red-600 text-white px-3 py-1 rounded-full text-sm font-semibold animate-pulse">
            LIVE
        </div>
    </div>

    <!-- Host Controls -->
    @if($isHost)
        <div class="mt-4 flex justify-center gap-4">
            @if(!$isStreaming)
                <button wire:click="startStream" 
                        class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition flex items-center gap-2">
                    <x-heroicon-o-video-camera class="w-5 h-5" />
                    Start Stream
                </button>
            @else
                <button wire:click="stopStream" 
                        class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition flex items-center gap-2">
                    <x-heroicon-o-video-camera-slash class="w-5 h-5" />
                    End Stream
                </button>
            @endif
        </div>
    @endif

    <!-- Chat -->
    <div class="mt-4 bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
        <div class="p-4 border-b dark:border-gray-700">
            <h4 class="font-semibold">Live Chat</h4>
        </div>
        
        <!-- Messages -->
        <div class="h-64 overflow-y-auto p-4 space-y-3" id="chatMessages">
            @foreach($chatMessages as $message)
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center text-white font-semibold">
                        {{ substr($message['user_name'], 0, 1) }}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-baseline gap-2">
                            <span class="font-semibold">{{ $message['user_name'] }}</span>
                            <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($message['timestamp'])->diffForHumans() }}</span>
                        </div>
                        <p class="text-gray-700 dark:text-gray-300">{{ $message['message'] }}</p>
                    </div>
                </div>
            @endforeach
            
            @if(empty($chatMessages))
                <p class="text-center text-gray-500 dark:text-gray-400">No messages yet</p>
            @endif
        </div>

        <!-- Message Input -->
        <div class="p-4 border-t dark:border-gray-700">
            <form wire:submit="sendMessage" class="flex gap-2">
                <input type="text" 
                       wire:model="newMessage"
                       placeholder="Type a message..."
                       class="flex-1 p-2 border rounded dark:bg-gray-700 dark:border-gray-600"
                       x-ref="messageInput"
                       @keydown.enter="$wire.submit">
                <button type="submit" 
                        class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
                    <x-heroicon-o-paper-airplane class="w-5 h-5" />
                </button>
            </form>
        </div>
    </div>

    @script
    <script>
        let localStream = null;
        let room = null;

        function init() {
            if (this.isHost && this.livekitToken) {
                connectAsHost();
            } else if (!this.isHost) {
                connectAsViewer();
            }
            
            // Listen for Livewire events
            Livewire.on('chatMessage', (message) => {
                this.chatMessages.push(message);
                scrollToBottom();
            });
            
            Livewire.on('viewerJoined', () => {
                this.viewerCount++;
            });
            
            Livewire.on('viewerLeft', () => {
                this.viewerCount--;
            });
        }

        async function connectAsHost() {
            try {
                const LiveKitClient = window.LiveKitClient;
                room = new LiveKitClient.Room();
                
                await room.connect('wss://your-livekit-server', this.livekitToken);
                
                localStream = await navigator.mediaDevices.getUserMedia({
                    audio: true,
                    video: true
                });
                
                const videoTrack = localStream.getVideoTracks()[0];
                const audioTrack = localStream.getAudioTracks()[0];
                
                if (videoTrack) {
                    await room.localParticipant.publishTrack(videoTrack);
                }
                if (audioTrack) {
                    await room.localParticipant.publishTrack(audioTrack);
                }
                
                const videoElement = document.getElementById('hostVideo');
                videoElement.srcObject = localStream;
                
            } catch (error) {
                console.error('Failed to connect as host:', error);
            }
        }

        async function connectAsViewer() {
            // Implement viewer connection logic
            // This would connect to the stream via CDN or WebRTC
        }

        function scrollToBottom() {
            const chatContainer = document.getElementById('chatMessages');
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    </script>
    @endscript
</div>
