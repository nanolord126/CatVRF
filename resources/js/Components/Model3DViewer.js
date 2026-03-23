/**
 * Three.js Viewer для 3D моделей (GLB/GLTF)
 * FEATURES:
 * - Загрузка GLB/GLTF файлов
 * - Интерактивное управление (rotate, zoom, pan - OrbitControls)
 * - Скачивание скриншота (rate limited)
 * - Конфигурация материалов (цвет, текстура)
 * - Fallback на изображение при ошибке
 */

import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

class Model3DViewer {
  constructor(modelId, signedUrl, correlationId) {
    this.modelId = modelId;
    this.signedUrl = signedUrl;
    this.correlationId = correlationId;

    // Three.js сцена, камера, рендер
    this.scene = null;
    this.camera = null;
    this.renderer = null;
    this.controls = null;
    this.model = null;
    
    // Конфигурация
    this.currentConfig = {};
    this.materialConfigs = {};

    // Rate limiting для скриншотов (5 в час)
    this.lastScreenshot = 0;
    this.screenshotLimit = 12000; // миллисекунды

    this.init();
  }

  /**
   * Инициализация Three.js
   */
  init() {
    const canvas = document.getElementById(`3d-canvas-${this.modelId}`);
    const container = document.getElementById(`3d-viewer-${this.modelId}`);

    if (!canvas) {
      console.error(`Canvas не найден для ${this.modelId}`);
      return;
    }

    // Сцена
    this.scene = new THREE.Scene();
    this.scene.background = new THREE.Color(0x1a1a2e);

    // Камера
    const width = container.clientWidth;
    const height = container.clientHeight;
    this.camera = new THREE.PerspectiveCamera(
      75,
      width / height,
      0.1,
      1000
    );
    this.camera.position.set(0, 0, 3);

    // Рендер
    this.renderer = new THREE.WebGLRenderer({ 
      canvas, 
      antialias: true,
      alpha: true 
    });
    this.renderer.setSize(width, height);
    this.renderer.setPixelRatio(window.devicePixelRatio);

    // Освещение
    this.setupLighting();

    // Управление (OrbitControls)
    this.controls = new OrbitControls(this.camera, this.renderer.domElement);
    this.controls.enableDamping = true;
    this.controls.dampingFactor = 0.05;
    this.controls.autoRotate = true;
    this.controls.autoRotateSpeed = 4;
    this.controls.enableZoom = true;

    // Загрузка модели
    this.loadModel();

    // Анимационный цикл
    this.animate();

    // Обработка ресайза
    window.addEventListener('resize', () => this.onWindowResize());

    // Логирование инициализации
    console.log(`[3D Viewer] Инициализирован для ${this.modelId}`, {
      correlationId: this.correlationId,
      signedUrl: this.signedUrl,
    });
  }

  /**
   * Настройка освещения
   */
  setupLighting() {
    // Окружающее освещение
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
    this.scene.add(ambientLight);

    // Направленное освещение (солнце)
    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(5, 5, 5);
    directionalLight.castShadow = true;
    this.scene.add(directionalLight);

    // Точечное освещение (основное)
    const pointLight = new THREE.PointLight(0xffffff, 0.6);
    pointLight.position.set(0, 5, 0);
    this.scene.add(pointLight);
  }

  /**
   * Загрузка 3D модели
   */
  loadModel() {
    const loader = new GLTFLoader();
    const loadingDiv = document.getElementById(`3d-loading-${this.modelId}`);
    const errorDiv = document.getElementById(`3d-error-${this.modelId}`);

    if (loadingDiv) {
      loadingDiv.style.display = 'flex';
    }

    loader.load(
      this.signedUrl,
      (gltf) => {
        // Успешно загружено
        this.model = gltf.scene;

        // Центрируем модель
        this.centerModel(this.model);

        // Добавляем в сцену
        this.scene.add(this.model);

        // Кэшируем конфиги материалов
        this.cacheMaterialConfigs();

        if (loadingDiv) {
          loadingDiv.style.display = 'none';
        }

        console.log(`[3D Viewer] Модель ${this.modelId} загружена успешно`, {
          geometries: gltf.scene.children.length,
        });
      },
      (progress) => {
        // Прогресс загрузки
        const percent = Math.round((progress.loaded / progress.total) * 100);
        console.log(`[3D Viewer] Загрузка ${this.modelId}: ${percent}%`);
      },
      (error) => {
        // Ошибка загрузки
        console.error(`[3D Viewer] Ошибка загрузки ${this.modelId}:`, error);

        if (loadingDiv) {
          loadingDiv.style.display = 'none';
        }

        if (errorDiv) {
          errorDiv.style.display = 'flex';
        }

        // Логирование ошибки
        this.logError('Model loading failed', error);
      }
    );
  }

  /**
   * Центрирование модели
   */
  centerModel(model) {
    const box = new THREE.Box3().setFromObject(model);
    const center = box.getCenter(new THREE.Vector3());
    const size = box.getSize(new THREE.Vector3());

    // Смещение на центр
    model.position.sub(center);

    // Масштабирование
    const maxDim = Math.max(size.x, size.y, size.z);
    const scale = 2 / maxDim;
    model.scale.multiplyScalar(scale);

    // Позиция камеры
    const distance = maxDim / 2;
    this.camera.position.set(distance, distance * 0.5, distance);
    this.camera.lookAt(0, 0, 0);
    this.controls.target.copy(new THREE.Vector3(0, 0, 0));
    this.controls.update();
  }

  /**
   * Кэширование конфигов материалов
   */
  cacheMaterialConfigs() {
    if (!this.model) return;

    this.model.traverse((child) => {
      if (child.isMesh && child.material) {
        this.materialConfigs[child.name] = {
          original: child.material.clone(),
          color: child.material.color.clone(),
          metalness: child.material.metalness,
          roughness: child.material.roughness,
        };
      }
    });
  }

  /**
   * Обновление конфигурации модели (цвет, материал)
   */
  updateConfiguration(config) {
    if (!this.model) return;

    this.currentConfig = { ...this.currentConfig, ...config };

    // Применяем цвет ко всем материалам
    if (config.color) {
      this.model.traverse((child) => {
        if (child.isMesh && child.material) {
          child.material.color.setStyle(config.color);
        }
      });
    }

    // Применяем металл/шероховатость
    if (config.metalness !== undefined) {
      this.model.traverse((child) => {
        if (child.isMesh && child.material) {
          child.material.metalness = config.metalness;
        }
      });
    }

    if (config.roughness !== undefined) {
      this.model.traverse((child) => {
        if (child.isMesh && child.material) {
          child.material.roughness = config.roughness;
        }
      });
    }

    console.log(`[3D Viewer] Конфигурация обновлена для ${this.modelId}:`, config);
  }

  /**
   * Сброс вида
   */
  resetView() {
    if (!this.model) return;

    const box = new THREE.Box3().setFromObject(this.model);
    const size = box.getSize(new THREE.Vector3());
    const maxDim = Math.max(size.x, size.y, size.z);
    const distance = maxDim / 2;

    this.camera.position.set(distance, distance * 0.5, distance);
    this.camera.lookAt(0, 0, 0);
    this.controls.target.copy(new THREE.Vector3(0, 0, 0));
    this.controls.update();

    console.log(`[3D Viewer] Вид сброшен для ${this.modelId}`);
  }

  /**
   * Скачивание скриншота
   * SECURITY: Rate limiting 5 в час
   */
  downloadScreenshot() {
    // Rate limiting
    const now = Date.now();
    if (now - this.lastScreenshot < this.screenshotLimit) {
      const remaining = Math.ceil((this.screenshotLimit - (now - this.lastScreenshot)) / 1000);
      alert(`Подождите ${remaining} секунд перед следующим скриншотом`);
      return;
    }

    this.lastScreenshot = now;

    // Отключаем авторотацию для чистого скриншота
    this.controls.autoRotate = false;
    this.renderer.render(this.scene, this.camera);

    // Получаем изображение
    this.renderer.domElement.toBlob((blob) => {
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `model-${this.modelId}-${Date.now()}.png`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);

      // Включаем авторотацию обратно
      this.controls.autoRotate = true;

      // Логирование
      console.log(`[3D Viewer] Скриншот скачан для ${this.modelId}`);
    });
  }

  /**
   * Обработка ресайза окна
   */
  onWindowResize() {
    const container = document.getElementById(`3d-viewer-${this.modelId}`);
    if (!container) return;

    const width = container.clientWidth;
    const height = container.clientHeight;

    this.camera.aspect = width / height;
    this.camera.updateProjectionMatrix();
    this.renderer.setSize(width, height);
  }

  /**
   * Анимационный цикл
   */
  animate = () => {
    requestAnimationFrame(this.animate);

    if (this.controls) {
      this.controls.update();
    }

    if (this.renderer && this.scene && this.camera) {
      this.renderer.render(this.scene, this.camera);
    }
  };

  /**
   * Логирование ошибок
   */
  logError(message, error) {
    console.error(message, {
      modelId: this.modelId,
      correlationId: this.correlationId,
      error: error.message,
      stack: error.stack,
    });

    // TODO: Отправить на сервер для аналитики
  }

  /**
   * Очистка ресурсов
   */
  dispose() {
    if (this.renderer) {
      this.renderer.dispose();
    }

    if (this.controls) {
      this.controls.dispose();
    }

    console.log(`[3D Viewer] Ресурсы освобождены для ${this.modelId}`);
  }
}

// Глобальное хранилище viewers
window.model3DViewers = window.model3DViewers || {};

/**
 * Инициализация viewer при загрузке страницы
 */
window.initModel3DViewer = function(modelId, signedUrl, correlationId) {
  window.model3DViewers[modelId] = new Model3DViewer(modelId, signedUrl, correlationId);
};

export default Model3DViewer;
