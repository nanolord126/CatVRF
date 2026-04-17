<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment';

interface Props {
  modelUrl: string;
  autoRotate?: boolean;
  backgroundColor?: string;
  showControls?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  autoRotate: true,
  backgroundColor: '#1a1a1a',
  showControls: true,
});

const emit = defineEmits<{
  modelLoaded: [];
  modelError: [error: Error];
  viewChanged: [cameraPosition: { x: number; y: number; z: number }];
}>();

const containerRef = ref<HTMLDivElement>();
let scene: THREE.Scene;
let camera: THREE.PerspectiveCamera;
let renderer: THREE.WebGLRenderer;
let controls: OrbitControls;
let animationId: number;
let model: THREE.Group;

const initThreeJS = () => {
  if (!containerRef.value) return;

  const width = containerRef.value.clientWidth;
  const height = containerRef.value.clientHeight;

  scene = new THREE.Scene();
  scene.background = new THREE.Color(props.backgroundColor);

  camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
  camera.position.set(5, 3, 5);

  renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setSize(width, height);
  renderer.setPixelRatio(window.devicePixelRatio);
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = THREE.PCFSoftShadowMap;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1;
  containerRef.value.appendChild(renderer.domElement);

  const environment = new RoomEnvironment();
  const pmremGenerator = new THREE.PMREMGenerator(renderer);
  scene.environment = pmremGenerator.fromScene(environment).texture;

  if (props.showControls) {
    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;
    controls.autoRotate = props.autoRotate;
    controls.autoRotateSpeed = 2.0;
    controls.enablePan = false;
    controls.minDistance = 3;
    controls.maxDistance = 15;
    controls.maxPolarAngle = Math.PI / 2;
  }

  const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
  scene.add(ambientLight);

  const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
  directionalLight.position.set(5, 10, 7);
  directionalLight.castShadow = true;
  directionalLight.shadow.mapSize.width = 2048;
  directionalLight.shadow.mapSize.height = 2048;
  scene.add(directionalLight);

  const fillLight = new THREE.DirectionalLight(0xffffff, 0.3);
  fillLight.position.set(-5, 5, -5);
  scene.add(fillLight);

  loadModel();

  window.addEventListener('resize', onWindowResize);
};

const loadModel = () => {
  const loader = new GLTFLoader();
  
  loader.load(
    props.modelUrl,
    (gltf) => {
      model = gltf.scene;
      
      const box = new THREE.Box3().setFromObject(model);
      const center = box.getCenter(new THREE.Vector3());
      const size = box.getSize(new THREE.Vector3());
      
      const maxDim = Math.max(size.x, size.y, size.z);
      const scale = 4 / maxDim;
      model.scale.setScalar(scale);
      
      model.position.sub(center.multiplyScalar(scale));
      model.position.y = -box.min.y * scale + 0.5;
      
      model.traverse((child) => {
        if (child instanceof THREE.Mesh) {
          child.castShadow = true;
          child.receiveShadow = true;
          
          if (child.material) {
            child.material.envMapIntensity = 1;
          }
        }
      });
      
      scene.add(model);
      emit('modelLoaded');
    },
    (progress) => {
      const percent = (progress.loaded / progress.total) * 100;
      console.log(`Loading model: ${percent.toFixed(2)}%`);
    },
    (error) => {
      console.error('Error loading model:', error);
      emit('modelError', error as Error);
    }
  );
};

const onWindowResize = () => {
  if (!containerRef.value || !camera || !renderer) return;
  
  const width = containerRef.value.clientWidth;
  const height = containerRef.value.clientHeight;
  
  camera.aspect = width / height;
  camera.updateProjectionMatrix();
  renderer.setSize(width, height);
};

const animate = () => {
  animationId = requestAnimationFrame(animate);
  
  if (controls) {
    controls.update();
  }
  
  if (model && props.autoRotate && !controls) {
    model.rotation.y += 0.005;
  }
  
  renderer.render(scene, camera);
};

const resetCamera = () => {
  if (camera && controls) {
    camera.position.set(5, 3, 5);
    controls.target.set(0, 0, 0);
    controls.update();
  }
};

const setCameraPosition = (x: number, y: number, z: number) => {
  if (camera) {
    camera.position.set(x, y, z);
    emit('viewChanged', { x, y, z });
  }
};

defineExpose({
  resetCamera,
  setCameraPosition,
});

onMounted(() => {
  initThreeJS();
  animate();
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', onWindowResize);
  cancelAnimationFrame(animationId);
  
  if (renderer) {
    renderer.dispose();
  }
  
  if (controls) {
    controls.dispose();
  }
  
  if (model) {
    scene.remove(model);
    model.traverse((child) => {
      if (child instanceof THREE.Mesh) {
        child.geometry.dispose();
        if (Array.isArray(child.material)) {
          child.material.forEach((material) => material.dispose());
        } else {
          child.material.dispose();
        }
      }
    });
  }
});

watch(() => props.modelUrl, () => {
  if (model) {
    scene.remove(model);
    model.traverse((child) => {
      if (child instanceof THREE.Mesh) {
        child.geometry.dispose();
        if (Array.isArray(child.material)) {
          child.material.forEach((material) => material.dispose());
        } else {
          child.material.dispose();
        }
      }
    });
  }
  loadModel();
});
</script>

<template>
  <div 
    ref="containerRef" 
    class="car-3d-viewer w-full h-full relative overflow-hidden"
    :style="{ backgroundColor }"
  >
    <div class="absolute top-4 left-4 z-10 flex gap-2">
      <button 
        @click="resetCamera"
        class="px-3 py-1.5 bg-white/90 backdrop-blur-sm rounded-lg shadow-md text-sm font-medium hover:bg-white transition-colors"
      >
        Сбросить вид
      </button>
    </div>
    <div class="absolute bottom-4 left-4 z-10 text-white/70 text-xs">
      Крутите для вращения • Колесико для зума
    </div>
  </div>
</template>

<style scoped>
.car-3d-viewer {
  border-radius: 12px;
  overflow: hidden;
}

.car-3d-viewer canvas {
  width: 100% !important;
  height: 100% !important;
}
</style>
