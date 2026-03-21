<div class="glassmorphism rounded-lg p-6" x-data="jewelry3d()">
    <h2 class="text-2xl font-bold text-white mb-6">3D Просмотр украшения</h2>

    <!-- 3D Viewer Canvas -->
    <div class="relative w-full h-96 bg-gradient-to-b from-gray-900 to-black rounded-lg mb-6 flex items-center justify-center overflow-hidden">
        <canvas 
            id="jewel-canvas"
            wire:ignore
            class="w-full h-full"
        ></canvas>

        <div class="absolute top-4 right-4 flex gap-2">
            <button 
                @click="startAR()"
                class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition"
                title="Дополненная реальность"
            >
                📱 AR
            </button>
            <button 
                @click="startVR()"
                class="px-3 py-1 bg-purple-600 text-white text-sm rounded hover:bg-purple-700 transition"
                title="Виртуальная реальность"
            >
                🥽 VR
            </button>
        </div>
    </div>

    <!-- Controls -->
    <div class="grid grid-cols-2 gap-4 mb-6">
        <!-- Rotation Controls -->
        <div class="bg-black/20 p-4 rounded-lg">
            <p class="text-white font-semibold mb-3">Вращение</p>
            <div class="space-y-2">
                <div>
                    <label class="text-gray-400 text-sm">X: <span class="text-amber-400">{{ round($rotationX) }}°</span></label>
                    <input 
                        type="range" 
                        min="0" 
                        max="360" 
                        wire:change="rotateX($event.target.value)"
                        class="w-full"
                    >
                </div>
                <div>
                    <label class="text-gray-400 text-sm">Y: <span class="text-amber-400">{{ round($rotationY) }}°</span></label>
                    <input 
                        type="range" 
                        min="0" 
                        max="360" 
                        wire:change="rotateY($event.target.value)"
                        class="w-full"
                    >
                </div>
                <div>
                    <label class="text-gray-400 text-sm">Z: <span class="text-amber-400">{{ round($rotationZ) }}°</span></label>
                    <input 
                        type="range" 
                        min="0" 
                        max="360" 
                        wire:change="rotateZ($event.target.value)"
                        class="w-full"
                    >
                </div>
            </div>
        </div>

        <!-- Zoom & Material -->
        <div class="bg-black/20 p-4 rounded-lg">
            <p class="text-white font-semibold mb-3">Параметры</p>
            <div class="space-y-2">
                <div>
                    <label class="text-gray-400 text-sm">Масштаб: <span class="text-amber-400">{{ round($zoom, 1) }}x</span></label>
                    <input 
                        type="range" 
                        min="0.1" 
                        max="5" 
                        step="0.1" 
                        wire:change="setZoom($event.target.value)"
                        class="w-full"
                    >
                </div>
                <div>
                    <label class="text-gray-400 text-sm mb-2 block">Материал:</label>
                    <select wire:change="changeMaterial($event.target.value)" class="w-full px-3 py-1 bg-gray-800 text-white text-sm rounded">
                        <option value="gold" {{ $materialType === 'gold' ? 'selected' : '' }}>Золото</option>
                        <option value="silver" {{ $materialType === 'silver' ? 'selected' : '' }}>Серебро</option>
                        <option value="platinum" {{ $materialType === 'platinum' ? 'selected' : '' }}>Платина</option>
                        <option value="rose_gold" {{ $materialType === 'rose_gold' ? 'selected' : '' }}>Розовое золото</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="grid grid-cols-4 gap-2">
        <button 
            wire:click="downloadModel('glb')"
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition"
        >
            📥 Скачать
        </button>
        <button 
            wire:click="shareModel"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition"
        >
            🔗 Поделиться
        </button>
        <button 
            @click="printModel()"
            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition"
        >
            🖨️ 3D Печать
        </button>
        <button 
            @click="resetView()"
            class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white text-sm rounded-lg transition"
        >
            ↺ Сброс
        </button>
    </div>
</div>

@script
<script>
    Alpine.data('jewelry3d', () => ({
        startAR() {
            $wire.enableAR();
            alert('AR режим включен! Откройте на мобильном устройстве с поддержкой AR');
        },
        startVR() {
            $wire.enableVR();
            alert('VR режим включен! Подключите VR-гарнитуру');
        },
        printModel() {
            alert('Модель подготовлена для 3D печати. Загрузите файл в слайсер');
        },
        resetView() {
            window.location.reload();
        },
    }));
</script>
@endscript
