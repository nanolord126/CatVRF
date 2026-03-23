{{-- 
  Компонент для отображения 3D карточки товара/услуги
  FEATURES:
  - Three.js поддержка для GLB/GLTF моделей
  - Интерактивные управления (rotate, zoom, pan)
  - Конфигуратор (цвет, материал, размер)
  - Скачивание скриншота (5/час rate limit)
  - Fallback на обычное изображение
--}}

<div class="3d-card-viewer" id="3d-viewer-{{ $modelId }}" wire:ignore>
  <!-- 3D Canvas -->
  <div class="3d-canvas-wrapper">
    <canvas id="3d-canvas-{{ $modelId }}" class="3d-canvas"></canvas>
    
    <!-- Загрузка -->
    <div class="3d-loading" id="3d-loading-{{ $modelId }}" style="display: none;">
      <div class="spinner"></div>
      <p>Загрузка 3D модели...</p>
    </div>

    <!-- Ошибка -->
    <div class="3d-error" id="3d-error-{{ $modelId }}" style="display: none;">
      <p>Ошибка загрузки 3D модели</p>
      <img src="{{ $fallbackImage }}" alt="{{ $modelName }}" class="fallback-image" />
    </div>
  </div>

  <!-- Управление -->
  <div class="3d-controls">
    <!-- Кнопки управления -->
    <button class="control-btn reset-btn" title="Сбросить вид" onclick="window.resetModel3DView('{{ $modelId }}')">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
        <path d="M4 10a6 6 0 0 1 10.39-3.65L9 6H13v4H9l3.27-3.27A4 4 0 0 0 4 10z"/>
      </svg>
    </button>

    <button class="control-btn fullscreen-btn" title="Полный экран" onclick="window.toggleFullscreen('{{ $modelId }}')">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
        <path d="M3 3h5v2H5v3H3V3zm12 0h2v5h-2V5h-3V3zm-3 12h3v2h-5v-2h2zm-7-2h2v3h3v2H3v-2z"/>
      </svg>
    </button>

    @if($allowScreenshot)
    <button class="control-btn screenshot-btn" title="Сохранить скриншот" onclick="window.downloadScreenshot('{{ $modelId }}')">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
        <path d="M4 5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5zm10 0H6v6h8V5z"/>
      </svg>
    </button>
    @endif
  </div>

  <!-- Конфигуратор (если есть варианты) -->
  @if($configurations->count() > 0)
  <div class="3d-configurator">
    <h4>Параметры модели</h4>
    
    @foreach($configurations as $config)
    <div class="config-group">
      <label>{{ $config->name }}</label>
      <select onchange="window.updateModel3DConfiguration('{{ $modelId }}', this.value)" class="config-select">
        @foreach(json_decode($config->config, true) as $key => $value)
        <option value="{{ json_encode([$key => $value]) }}">
          {{ ucfirst($key) }}: {{ $value }}
        </option>
        @endforeach
      </select>
    </div>
    @endforeach

    <!-- Цена с модификатором -->
    <div class="price-display">
      <span class="label">Цена:</span>
      <span class="price" id="3d-price-{{ $modelId }}">{{ $basePrice }}</span> ₽
    </div>
  </div>
  @endif

  <!-- Скрытый инпут для корреляции -->
  <input type="hidden" id="correlation-id-{{ $modelId }}" value="{{ $correlationId }}" />
  <input type="hidden" id="model-uuid-{{ $modelId }}" value="{{ $modelUuid }}" />
  <input type="hidden" id="model-signed-url-{{ $modelId }}" value="{{ $modelSignedUrl }}" />
</div>

<style>
.3d-card-viewer {
  background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
  border-radius: 12px;
  padding: 16px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.2);
}

.3d-canvas-wrapper {
  position: relative;
  width: 100%;
  aspect-ratio: 1;
  border-radius: 8px;
  overflow: hidden;
  background: linear-gradient(45deg, #1a1a2e, #16213e);
  margin-bottom: 16px;
}

.3d-canvas {
  width: 100%;
  height: 100%;
  display: block;
}

.3d-loading, .3d-error {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 12px;
  background: rgba(0,0,0,0.8);
  color: white;
  font-size: 14px;
}

.3d-error .fallback-image {
  max-width: 80%;
  max-height: 80%;
  object-fit: contain;
}

.3d-controls {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
}

.control-btn {
  background: rgba(255,255,255,0.1);
  border: 1px solid rgba(255,255,255,0.2);
  color: white;
  width: 40px;
  height: 40px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.control-btn:hover {
  background: rgba(255,255,255,0.2);
  border-color: rgba(255,255,255,0.4);
}

.3d-configurator {
  background: rgba(255,255,255,0.05);
  border-radius: 8px;
  padding: 12px;
}

.config-group {
  margin-bottom: 12px;
}

.config-group label {
  display: block;
  font-size: 12px;
  color: rgba(255,255,255,0.7);
  margin-bottom: 4px;
  text-transform: uppercase;
}

.config-select {
  width: 100%;
  padding: 8px;
  background: rgba(255,255,255,0.1);
  border: 1px solid rgba(255,255,255,0.2);
  color: white;
  border-radius: 4px;
  font-size: 14px;
}

.price-display {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 12px;
  border-top: 1px solid rgba(255,255,255,0.1);
  margin-top: 12px;
}

.price {
  font-size: 24px;
  font-weight: bold;
  color: #4ade80;
}

@media (max-width: 768px) {
  .3d-card-viewer {
    padding: 12px;
  }
  
  .3d-controls {
    gap: 6px;
  }
  
  .control-btn {
    width: 36px;
    height: 36px;
  }
}
</style>

<script>
/**
 * Инициализация Three.js viewer для 3D модели
 */
document.addEventListener('DOMContentLoaded', function() {
  const modelId = '{{ $modelId }}';
  const signedUrl = document.getElementById('model-signed-url-' + modelId).value;
  const correlationId = document.getElementById('correlation-id-' + modelId).value;
  
  window.initModel3DViewer(modelId, signedUrl, correlationId);
});

/**
 * Функции управления Three.js viewer
 * (реализованы в resources/js/components/Model3DViewer.js)
 */
window.resetModel3DView = function(modelId) {
  if (window.model3DViewers && window.model3DViewers[modelId]) {
    window.model3DViewers[modelId].resetView();
  }
};

window.toggleFullscreen = function(modelId) {
  const container = document.getElementById('3d-viewer-' + modelId);
  if (document.fullscreenElement) {
    document.exitFullscreen();
  } else {
    container.requestFullscreen().catch(err => console.error(err));
  }
};

window.downloadScreenshot = function(modelId) {
  if (window.model3DViewers && window.model3DViewers[modelId]) {
    window.model3DViewers[modelId].downloadScreenshot();
  }
};

window.updateModel3DConfiguration = function(modelId, configJson) {
  if (window.model3DViewers && window.model3DViewers[modelId]) {
    const config = JSON.parse(configJson);
    window.model3DViewers[modelId].updateConfiguration(config);
  }
};
</script>
