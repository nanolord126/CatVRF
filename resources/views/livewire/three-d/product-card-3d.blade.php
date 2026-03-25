<div class="relative w-full h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- 3D Canvas Container -->
    <div id="canvas-3d-product" class="absolute inset-0 rounded-lg shadow-2xl"></div>

    <!-- Controls Overlay -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 flex gap-4 z-50">
        <button wire:click="rotate('left')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            ← Влево
        </button>
        <button wire:click="rotate('up')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            ↑ Вверх
        </button>
        <button wire:click="rotate('down')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            ↓ Вниз
        </button>
        <button wire:click="rotate('right')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            Вправо →
        </button>
    </div>

    <!-- Zoom Controls -->
    <div class="absolute right-8 top-1/2 transform -translate-y-1/2 flex flex-col gap-4 z-50">
        <button wire:click="zoomIn()" class="px-3 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
            + Увеличить
        </button>
        <button wire:click="zoomOut()" class="px-3 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
            - Уменьшить
        </button>
    </div>

    <!-- Color Variants -->
    <div class="absolute left-8 top-8 flex flex-col gap-4 z-50">
        <h3 class="text-white font-semibold">Цвета</h3>
        <div class="flex gap-3">
            @foreach(['#000000', '#FFFFFF', '#FF0000', '#0000FF'] as $color)
                <button wire:click="changeColor('{{ $color }}')"
                    class="w-10 h-10 rounded-lg border-2 cursor-pointer transition"
                    style="background-color: {{ $color }}; border-color: {{ $this->selectedColor === $color ? '#00FF00' : 'transparent' }}">
                </button>
            @endforeach
        </div>
    </div>

    <!-- AR Button -->
    <div class="absolute top-8 right-8 z-50">
        <button wire:click="enableARView()" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition font-semibold">
            🔮 AR Просмотр
        </button>
    </div>

    <!-- Product Info -->
    <div class="absolute top-8 left-1/2 transform -translate-x-1/2 bg-slate-800/90 backdrop-blur px-6 py-4 rounded-lg z-50">
        <h2 class="text-white text-xl font-bold">3D Просмотр товара</h2>
        <p class="text-slate-300 text-sm mt-2">Вращение, масштабирование, выбор цвета</p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script>
    // Three.js 3D Viewer Implementation
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });

    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setClearColor(0x1e293b, 1);
    document.getElementById('canvas-3d-product').appendChild(renderer.domElement);

    // Lighting
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
    scene.aconst directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(5, 10, 5);
    scene.a// Load 3D Model (GLB/GLTF)
    const loader = new THREE.GLTFLoader();
    let model;

    loader.load('{{ $model3D["url"] ?? "/3d-models/default-product.glb" }}', (gltf) => {
        model = gltf.scene;
        scene.amodel.position.set(0, 0, 0);
        model.scale.set({{ $model3D['scale'] ?? 1 }}, {{ $model3D['scale'] ?? 1 }}, {{ $model3D['scale'] ?? 1 }});
    });

    camera.position.z = 2;

    const animate = () => {
        requestAnimationFrame(animate);

        if (model) {
            model.rotation.x = {{ $rotationX }} * Math.PI / 180;
            model.rotation.y = {{ $rotationY }} * Math.PI / 180;
            camera.zoom = {{ $zoom }};
            camera.updateProjectionMatrix();
        }

        renderer.render(scene, camera);
    };

    animate();

    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
</script>
