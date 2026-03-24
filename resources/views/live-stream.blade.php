@extends('layouts.app')

@section('title', $title . ' - Live')

@section('content')
<div x-data="liveStream({{ $stream->id }})" class="w-full min-h-screen bg-gradient-to-b from-gray-900 to-black flex flex-col">
    
    <!-- Header -->
    <div class="bg-black/50 backdrop-blur border-b border-gray-700 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="{{ route('filament.tenant.pages.dashboard') }}" class="text-gray-400 hover:text-white">
                ← Back
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white">{{ $stream->name }}</h1>
                <p class="text-sm text-gray-400">
                    @if($stream->is_live)
                        <span class="flex items-center gap-1 text-red-500">
                            <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                            🔴 LIVE
                        </span>
                    @else
                        <span class="text-gray-500">⚪ Offline</span>
                    @endif
                </p>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="flex gap-8 text-right">
            <div>
                <div class="text-gray-400 text-xs uppercase">Viewers</div>
                <div class="text-2xl font-bold text-white" x-text="viewers">0</div>
            </div>
            <div>
                <div class="text-gray-400 text-xs uppercase">Duration</div>
                <div class="text-2xl font-bold text-white" x-text="duration">00:00</div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex gap-6 p-6 max-w-7xl mx-auto w-full">
        
        <!-- Video Area (left side) -->
        <div class="flex-1 flex flex-col gap-4">
            <!-- Local/Primary Video -->
            <div class="bg-black rounded-lg overflow-hidden aspect-video flex items-center justify-center border border-gray-700">
                <video 
                    id="local-video" 
                    autoplay 
                    muted 
                    class="w-full h-full object-cover"
                    style="display: none;"
                ></video>
                <div id="loading" class="flex flex-col items-center gap-4">
                    <div class="w-12 h-12 border-4 border-gray-700 border-t-blue-500 rounded-full animate-spin"></div>
                    <p class="text-gray-400">Initializing camera...</p>
                </div>
            </div>

            <!-- Remote Peers Grid -->
            <div id="remote-videos" class="grid grid-cols-2 gap-4 auto-rows-max">
                <!-- Remote videos will be added here dynamically -->
            </div>
        </div>

        <!-- Chat/Info Sidebar (right side) -->
        <div class="w-80 flex flex-col gap-4">
            
            <!-- Event Info Card -->
            <div class="bg-gray-900 border border-gray-700 rounded-lg p-4">
                <h3 class="font-bold text-white mb-3">Event Info</h3>
                <div class="space-y-2 text-sm">
                    <div>
                        <span class="text-gray-500">📍 Location</span>
                        <p class="text-white">{{ $stream->location }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">⏰ Time</span>
                        <p class="text-white">{{ $stream->start_datetime->format('d.m.Y H:i') }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">👥 Capacity</span>
                        <p class="text-white">{{ $stream->total_capacity }} people</p>
                    </div>
                </div>
            </div>

            <!-- Controls Card -->
            <div class="bg-gray-900 border border-gray-700 rounded-lg p-4">
                <h3 class="font-bold text-white mb-3">Controls</h3>
                <div class="space-y-2">
                    <button 
                        @click="toggleAudio()"
                        class="w-full px-4 py-2 rounded bg-gray-800 hover:bg-gray-700 text-white transition"
                        :class="audioEnabled ? 'text-green-400' : 'text-red-400'"
                    >
                        <span x-text="audioEnabled ? '🎤 Audio On' : '🔇 Audio Off'"></span>
                    </button>
                    <button 
                        @click="toggleVideo()"
                        class="w-full px-4 py-2 rounded bg-gray-800 hover:bg-gray-700 text-white transition"
                        :class="videoEnabled ? 'text-green-400' : 'text-red-400'"
                    >
                        <span x-text="videoEnabled ? '📹 Video On' : '📹 Video Off'"></span>
                    </button>
                    <button 
                        @click="endStream()"
                        class="w-full px-4 py-2 rounded bg-red-600 hover:bg-red-700 text-white transition font-bold"
                    >
                        End Stream
                    </button>
                </div>
            </div>

            <!-- Connection Status -->
            <div class="bg-gray-900 border border-gray-700 rounded-lg p-4">
                <h3 class="font-bold text-white mb-3">Connection</h3>
                <div class="space-y-2 text-sm font-mono">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span 
                            :class="connectionStatus === 'connected' ? 'text-green-400' : 'text-yellow-400'"
                            x-text="connectionStatus"
                        ></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Peers</span>
                        <span class="text-white" x-text="peers.length"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Bandwidth</span>
                        <span class="text-white" x-text="bandwidth"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alpine.js Data & Script -->
<script type="module">
    import Alpine from 'alpinejs';
    
    window.Echo.channel('stream.{{ $stream->id }}')
        .listen('PeerJoined', (e) => {
            console.log('🎯 Peer joined:', e);
        })
        .listen('OfferSent', (e) => {
            console.log('📤 Offer received:', e);
        });

    Alpine.data('liveStream', () => ({
        streamId: {{ $stream->id }},
        localStream: null,
        peerConnections: new Map(),
        peers: [],
        viewers: 0,
        duration: '00:00',
        audioEnabled: true,
        videoEnabled: true,
        connectionStatus: 'disconnected',
        bandwidth: '0 Kbps',
        startTime: null,
        
        async init() {
            try {
                const response = await fetch('/mesh/join', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        stream_id: this.streamId,
                        peer_id: 'peer_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                    }),
                });

                const data = await response.json();
                console.log('✅ Room joined:', data);
                
                // Get local media
                this.localStream = await navigator.mediaDevices.getUserMedia({
                    video: { width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: { echoCancellation: true, noiseSuppression: true },
                });

                const videoElement = document.getElementById('local-video');
                videoElement.srcObject = this.localStream;
                videoElement.style.display = 'block';
                document.getElementById('loading').style.display = 'none';

                this.connectionStatus = 'connected';
                this.startTime = Date.now();
                this.updateDuration();

                // Subscribe to peers
                window.Echo.channel('stream.' + this.streamId)
                    .listen('PeerJoined', (e) => this.handlePeerJoined(e))
                    .listen('OfferSent', (e) => this.handleOffer(e))
                    .listen('AnswerSent', (e) => this.handleAnswer(e))
                    .listen('IceCandidateSent', (e) => this.handleIceCandidate(e));

            } catch (error) {
                console.error('❌ Error:', error);
                alert('Camera/Microphone access denied: ' + error.message);
            }
        },

        async handlePeerJoined(event) {
            console.log('👥 Peer joined:', event.peer_id);
            this.viewers++;
            // In real implementation, create RTCPeerConnection and send offer
        },

        toggleAudio() {
            this.audioEnabled = !this.audioEnabled;
            this.localStream.getAudioTracks().forEach(track => {
                track.enabled = this.audioEnabled;
            });
        },

        toggleVideo() {
            this.videoEnabled = !this.videoEnabled;
            this.localStream.getVideoTracks().forEach(track => {
                track.enabled = this.videoEnabled;
            });
        },

        endStream() {
            if (confirm('End stream?')) {
                this.localStream.getTracks().forEach(track => track.stop());
                window.location.href = '{{ route("filament.tenant.pages.dashboard") }}';
            }
        },

        updateDuration() {
            if (this.startTime) {
                const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
                const hours = Math.floor(elapsed / 3600);
                const minutes = Math.floor((elapsed % 3600) / 60);
                const seconds = elapsed % 60;
                
                this.duration = [hours, minutes, seconds]
                    .map(v => String(v).padStart(2, '0'))
                    .join(':');
                
                setTimeout(() => this.updateDuration(), 1000);
            }
        },
    }));
</script>

@endsection
