<div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">3D Тур по квартире</h1>
            <p class="text-slate-300">Этаж {{ $currentFloor + 1 }} / {{ $propertyData['floors'] ?? 1 }}</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- 3D Viewer -->
            <div class="lg:col-span-3 relative">
                <div id="canvas-property-3d" class="w-full h-[600px] bg-slate-800 rounded-lg shadow-2xl overflow-hidden"></div>

                <!-- Navigation Buttons -->
                <div class="absolute bottom-4 left-4 right-4 flex justify-between z-50">
                    <button wire:click="previousFloor()" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition {{ $currentFloor === 0 ? 'opacity-50 cursor-not-allowed' : '' }}">
                        ← Предыдущий этаж
                    </button>
                    <button wire:click="nextFloor()" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition {{ $currentFloor >= ($propertyData['floors'] - 1) ? 'opacity-50 cursor-not-allowed' : '' }}">
                        Следующий этаж →
                    </button>
                    <button wire:click="toggleAR()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                        🔮 AR Вид
                    </button>
                </div>
            </div>

            <!-- Room List -->
            <div class="lg:col-span-1">
                <div class="bg-slate-800 rounded-lg p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Комнаты на этаже {{ $currentFloor + 1 }}</h2>
                    <div class="space-y-2">
                        @forelse($propertyData['rooms'] ?? [] as $index => $room)
                            @if($room['floor'] === $currentFloor)
                                <button wire:click="selectRoom({{ $index }})"
                                    class="w-full px-4 py-3 text-left rounded-lg transition {{ $currentRoom['id'] === $room['id'] ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }}">
                                    <div class="font-semibold">{{ $room['name'] ?? 'Комната' }}</div>
                                    <div class="text-sm opacity-75">ID: {{ $room['id'] }}</div>
                                </button>
                            @endif
                        @empty
                            <p class="text-slate-400 text-sm">На этом этаже нет комнат</p>
                        @endforelse
                    </div>

                    <!-- Current Room Info -->
                    @if($currentRoom)
                        <div class="mt-6 pt-6 border-t border-slate-700">
                            <h3 class="font-semibold text-white mb-3">Информация о комнате</h3>
                            <div class="space-y-2 text-sm text-slate-300">
                                <p><strong>Название:</strong> {{ $currentRoom['name'] ?? 'Неизвестно' }}</p>
                                <p><strong>Тип:</strong> Спальня</p>
                                <p><strong>Размер:</strong> 4м × 5м</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script>
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x1a1a2e);

    const camera = new THREE.PerspectiveCamera(75, document.getElementById('canvas-property-3d').clientWidth / 600, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(document.getElementById('canvas-property-3d').clientWidth, 600);
    document.getElementById('canvas-property-3d').appendChild(renderer.domElement);

    // Lighting setup
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
    scene.aconst directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(10, 15, 10);
    scene.acamera.position.z = 3;

    const animate = () => {
        requestAnimationFrame(animate);
        renderer.render(scene, camera);
    };

    animate();
</script>
