<div class="relative w-full h-screen bg-gradient-to-br from-indigo-900 via-slate-900 to-slate-900">
    <!-- 3D Room Canvas -->
    <div id="canvas-room-3d" class="absolute inset-0"></div>

    <!-- Navigation Controls -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 flex gap-3 z-50">
        <button wire:click="viewFrom('bed')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            Кровать
        </button>
        <button wire:click="viewFrom('window')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            Окно
        </button>
        <button wire:click="viewFrom('door')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            Дверь
        </button>
        <button wire:click="viewFrom('full')" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
            Полный вид
        </button>
    </div>

    <!-- Floor Plan Toggle -->
    <div class="absolute top-8 right-8 z-50">
        <button wire:click="toggleFloorPlan()" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition font-semibold">
            📐 План номера
        </button>
    </div>

    <!-- Room Info -->
    <div class="absolute top-8 left-8 bg-slate-800/90 backdrop-blur px-6 py-4 rounded-lg z-50 max-w-sm">
        <h2 class="text-white text-xl font-bold">{{ $roomData['type'] ?? 'Номер' }}</h2>
        <div class="text-slate-300 text-sm mt-3 space-y-1">
            <p>📏 Размер: {{ $roomData['dimensions']['length'] ?? 5 }}м × {{ $roomData['dimensions']['width'] ?? 4 }}м</p>
            <p>📌 Удобства: {{ count($roomData['furniture'] ?? []) }} предметов</p>
        </div>
    </div>

    <!-- Floor Plan Overlay -->
    @if($showFloorPlan)
        <div class="absolute top-1/2 right-8 transform -translate-y-1/2 bg-white rounded-lg shadow-2xl p-6 w-80 z-50">
            <h3 class="text-lg font-bold text-slate-900 mb-4">План номера</h3>
            <svg class="w-full h-64 bg-slate-50 rounded" viewBox="0 0 500 400">
                <!-- Room walls -->
                <rect x="10" y="10" width="480" height="380" fill="none" stroke="#000" stroke-width="2"/>
                <!-- Door -->
                <circle cx="10" cy="100" r="30" fill="none" stroke="#FF6B6B" stroke-width="2"/>
                <!-- Window -->
                <rect x="250" y="10" width="60" height="5" fill="#87CEEB" stroke="#000" stroke-width="1"/>
                <!-- Furniture -->
                <rect x="50" y="50" width="120" height="80" fill="#D4A574" stroke="#000" stroke-width="1"/>
                <text x="110" y="95" text-anchor="middle" font-size="12" fill="#000">Кровать</text>
            </svg>
        </div>
    @endif
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script>
    // Three.js Room Viewer
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x1e293b);

    const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.shadowMap.enabled = true;
    document.getElementById('canvas-room-3d').appendChild(renderer.domElement);

    // Lighting
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
    scene.aconst directionalLight = new THREE.DirectionalLight(0xffffff, 0.9);
    directionalLight.position.set(5, 8, 5);
    directionalLight.castShadow = true;
    scene.a// Create Room
    const roomWidth = 5;
    const roomDepth = 4;
    const roomHeight = 2.8;

    // Walls
    const wallMaterial = new THREE.MeshStandardMaterial({ color: 0xe8e8e8 });
    const walls = [
        { pos: [0, roomHeight/2, -roomDepth/2], size: [roomWidth, roomHeight, 0.1] }, // back
        { pos: [0, roomHeight/2, roomDepth/2], size: [roomWidth, roomHeight, 0.1] }, // front
        { pos: [-roomWidth/2, roomHeight/2, 0], size: [0.1, roomHeight, roomDepth] }, // left
        { pos: [roomWidth/2, roomHeight/2, 0], size: [0.1, roomHeight, roomDepth] }, // right
    ];

    walls.forEach(wall => {
        const geometry = new THREE.BoxGeometry(...wall.size);
        const mesh = new THREE.Mesh(geometry, wallMaterial);
        mesh.position.set(...wall.pos);
        scene.a});

    // Floor
    const floorGeometry = new THREE.PlaneGeometry(roomWidth, roomDepth);
    const floorMaterial = new THREE.MeshStandardMaterial({ color: 0xd4af8f });
    const floor = new THREE.Mesh(floorGeometry, floorMaterial);
    floor.rotation.x = -Math.PI / 2;
    scene.a// Ceiling
    const ceilingGeometry = new THREE.PlaneGeometry(roomWidth, roomDepth);
    const ceilingMaterial = new THREE.MeshStandardMaterial({ color: 0xffffff });
    const ceiling = new THREE.Mesh(ceilingGeometry, ceilingMaterial);
    ceiling.rotation.x = Math.PI / 2;
    ceiling.position.y = roomHeight;
    scene.a// Add furniture
    const bedroomGeo = new THREE.BoxGeometry(1.6, 0.5, 2);
    const bedMat = new THREE.MeshStandardMaterial({ color: 0x8b4513 });
    const bed = new THREE.Mesh(bedroomGeo, bedMat);
    bed.position.set(-1.5, 0.25, 0);
    scene.acamera.position.set({{ $currentView['position'][0] ?? 0 }}, {{ $currentView['position'][1] ?? 1.5 }}, {{ $currentView['position'][2] ?? 0 }});
    camera.lookAt({{ $currentView['target'][0] ?? 0 }}, {{ $currentView['target'][1] ?? 1 }}, {{ $currentView['target'][2] ?? 0 }});

    const animate = () => {
        requestAnimationFrame(animate);
        renderer.render(scene, camera);
    };

    animate();

    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
</script>
