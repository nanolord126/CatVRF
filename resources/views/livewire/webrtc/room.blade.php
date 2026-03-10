<div x-data="webRTC('{{ $roomId }}', {{ json_encode($turnConfig) }})" class="video-container">
    <video x-ref="localVideo" autoplay playsinline muted></video>
    <video x-ref="remoteVideo" autoplay playsinline></video>

    <div class="controls">
        <button @click="toggleMic">🎤</button>
        <button @click="toggleVideo">📷</button>
        <button @click="endCall" class="bg-red-500">📵</button>
    </div>

    <script src="/js/webrtc-signaling.js"></script>
</div>
<style>
    .video-container { position: relative; height: 100vh; background: #000; }
    video { width: 45%; border: 1px solid #333; }
    .controls { position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); }
</style>
