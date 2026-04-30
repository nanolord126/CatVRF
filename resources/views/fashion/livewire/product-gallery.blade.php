<div>
    <div class="space-y-4">
        <!-- Main Image -->
        <div class="relative bg-gray-100 rounded-lg overflow-hidden aspect-square">
            @if($currentImage = $this->getCurrentImage())
            <img
                src="{{ $currentImage }}"
                alt="Product image"
                class="w-full h-full object-contain @if($showZoom) cursor-zoom-out @else cursor-zoom-in @endif"
                wire:click="toggleZoom"
            >
            @else
            <div class="flex items-center justify-center h-full text-gray-400">
                Нет изображений
            </div>
            @endif

            <!-- Navigation Arrows -->
            @if(count($images) > 1)
            <button
                wire:click="previousImage"
                class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white rounded-full p-2 shadow"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button
                wire:click="nextImage"
                class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white rounded-full p-2 shadow"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            @endif

            <!-- Image Counter -->
            @if(count($images) > 1)
            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 bg-black/50 text-white px-3 py-1 rounded-full text-sm">
                {{ $currentIndex + 1 }} / {{ count($images) }}
            </div>
            @endif
        </div>

        <!-- Thumbnails -->
        @if(count($images) > 1)
        <div class="flex gap-2 overflow-x-auto pb-2">
            @foreach($images as $index => $image)
            <button
                wire:click="selectImage({{ $index }})"
                class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2
                    @if($currentIndex === $index) border-blue-500 @else border-transparent hover:border-gray-300 @endif"
            >
                <img src="{{ $image }}" alt="Thumbnail" class="w-full h-full object-cover">
            </button>
            @endforeach
        </div>
        @endif

        <!-- Required Angles Warning -->
        @if(!$this->hasRequiredAngles())
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <span class="text-yellow-800 font-medium">Отсутствуют обязательные ракурсы</span>
                    <p class="text-sm text-yellow-700 mt-1">
                        Требуемые ракурсы: {{ implode(', ', self::REQUIRED_ANGLES) }}
                    </p>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
