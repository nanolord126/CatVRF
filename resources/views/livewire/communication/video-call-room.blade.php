<div class="relative bg-slate-900 aspect-video rounded-xl p-4">
    <div id="video-grid" class="grid grid-cols-2 gap-4 h-full">
        <div class="bg-black rounded-lg overflow-hidden flex items-center justify-center">
            @if($isCameraOn) <span class="text-white">Local Stream Active</span> @else <span class="text-red-500">Camera Off</span> @endif
        </div>
        <div class="bg-black rounded-lg overflow-hidden flex items-center justify-center">
            <span class="text-white">Remote Peer ID: {{ $roomId }}</span>
        </div>
    </div>
    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex space-x-4">
        <button wire:click="toggleMic" class="{{ $isMicOn ? 'bg-blue-600' : 'bg-red-600' }} p-3 rounded-full text-white">Mic</button>
        <button wire:click="toggleCamera" class="{{ $isCameraOn ? 'bg-blue-600' : 'bg-red-600' }} p-3 rounded-full text-white">Cam</button>
        <button class="bg-red-800 p-3 rounded-full text-white">End Call</button>
    </div>
</div>
