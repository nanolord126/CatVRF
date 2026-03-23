@props(['tenantId', 'vertical' => null, 'fromDate' => null, 'toDate' => null, 'height' => '600px', 'correlationId' => null])

<div class="geo-heatmap-container" data-tenant-id="{{ $tenantId }}" data-correlation-id="{{ $correlationId ?? \Illuminate\Support\Str::uuid() }}">
    <!-- Header with Filters -->
    <div class="geo-heatmap-header">
        <div class="heatmap-title-section">
            <h3 class="heatmap-title">Географическая тепловая карта активности</h3>
            <p class="heatmap-subtitle">Визуализация активности пользователей по географическим зонам</p>
        </div>

        <div class="heatmap-controls">
            <!-- Vertical Filter -->
            <div class="control-group">
                <label for="vertical-filter-{{ $tenantId }}">Категория / Вертикаль:</label>
                <select id="vertical-filter-{{ $tenantId }}" class="form-control vertical-filter" data-heatmap="geo">
                    <option value="">Все категории</option>
                    <option value="beauty" {{ $vertical === 'beauty' ? 'selected' : '' }}>Красота / Салоны</option>
                    <option value="food" {{ $vertical === 'food' ? 'selected' : '' }}>Еда / Доставка</option>
                    <option value="auto" {{ $vertical === 'auto' ? 'selected' : '' }}>Авто / Такси</option>
                    <option value="hotels" {{ $vertical === 'hotels' ? 'selected' : '' }}>Гостиницы / Отели</option>
                    <option value="realestate" {{ $vertical === 'realestate' ? 'selected' : '' }}>Недвижимость</option>
                </select>
            </div>

            <!-- Date Range Filter -->
            <div class="control-group">
                <label for="date-range-{{ $tenantId }}">Период:</label>
                <input 
                    type="text" 
                    id="date-range-{{ $tenantId }}" 
                    class="form-control date-range-picker" 
                    placeholder="Выберите диапазон дат"
                    data-start-date="{{ $fromDate ?? now()->subDays(30)->toDateString() }}"
                    data-end-date="{{ $toDate ?? now()->toDateString() }}"
                />
            </div>

            <!-- Activity Type Filter -->
            <div class="control-group">
                <label for="activity-type-{{ $tenantId }}">Тип активности:</label>
                <select id="activity-type-{{ $tenantId }}" class="form-control activity-type-filter" data-heatmap="geo">
                    <option value="">Все типы</option>
                    <option value="view">Просмотры</option>
                    <option value="purchase">Покупки</option>
                    <option value="booking">Бронирования</option>
                    <option value="order">Заказы</option>
                </select>
            </div>

            <!-- Refresh Button -->
            <div class="control-group">
                <button class="btn btn-primary btn-refresh-heatmap" data-heatmap="geo">
                    <i class="fas fa-sync-alt"></i> Обновить
                </button>
            </div>

            <!-- Export Buttons -->
            <div class="control-group">
                <button class="btn btn-success btn-export-heatmap" data-heatmap="geo" data-format="png" title="Экспортировать как PNG">
                    <i class="fas fa-image"></i> PNG
                </button>
            </div>
            <div class="control-group">
                <button class="btn btn-info btn-export-heatmap" data-heatmap="geo" data-format="pdf" title="Экспортировать как PDF">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div class="geo-heatmap-map-wrapper">
        <div 
            id="geo-heatmap-map-{{ $tenantId }}" 
            class="geo-heatmap-map" 
            style="height: {{ $height }}"
            data-map-container="true"
        ></div>

        <!-- Legend -->
        <div class="heatmap-legend">
            <h4 class="legend-title">Интенсивность</h4>
            <div class="legend-items">
                <div class="legend-item">
                    <span class="legend-color" style="background: #0000ff;"></span>
                    <span class="legend-label">Низкая (холодная)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: #00ff00;"></span>
                    <span class="legend-label">Средняя</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: #ffff00;"></span>
                    <span class="legend-label">Высокая</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: #ff0000;"></span>
                    <span class="legend-label">Очень высокая (горячая)</span>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div class="heatmap-loading" id="geo-heatmap-loading-{{ $tenantId }}" style="display: none;">
            <div class="spinner"></div>
            <p>Загрузка тепловой карты...</p>
        </div>

        <!-- Error Message -->
        <div class="heatmap-error" id="geo-heatmap-error-{{ $tenantId }}" style="display: none;">
            <p id="geo-heatmap-error-message">Ошибка при загрузке данных</p>
        </div>
    </div>

    <!-- Statistics Panel -->
    <div class="geo-heatmap-stats">
        <div class="stat-item">
            <span class="stat-label">Точек данных:</span>
            <span class="stat-value" id="geo-heatmap-points-{{ $tenantId }}">0</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Максимум в точке:</span>
            <span class="stat-value" id="geo-heatmap-max-{{ $tenantId }}">0</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Среднее значение:</span>
            <span class="stat-value" id="geo-heatmap-avg-{{ $tenantId }}">0</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Покрыто городов:</span>
            <span class="stat-value" id="geo-heatmap-cities-{{ $tenantId }}">0</span>
        </div>
    </div>
</div>

<style>
    .geo-heatmap-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 20px;
    }

    .geo-heatmap-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 20px;
        flex-wrap: wrap;
    }

    .heatmap-title-section {
        flex: 1;
        min-width: 300px;
    }

    .heatmap-title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a1a1a;
    }

    .heatmap-subtitle {
        margin: 5px 0 0 0;
        font-size: 0.9rem;
        color: #666;
    }

    .heatmap-controls {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
    }

    .control-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .control-group label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #333;
    }

    .control-group .form-control {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.9rem;
        min-width: 150px;
    }

    .control-group .form-control:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }

    .control-group .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4);
    }

    .btn-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
        color: white;
    }

    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
    }

    .geo-heatmap-map-wrapper {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .geo-heatmap-map {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 8px;
    }

    .heatmap-legend {
        position: absolute;
        bottom: 20px;
        left: 20px;
        background: rgba(255, 255, 255, 0.95);
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        z-index: 400;
    }

    .legend-title {
        margin: 0 0 10px 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #1a1a1a;
    }

    .legend-items {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
    }

    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 3px;
        border: 1px solid rgba(0, 0, 0, 0.2);
    }

    .legend-label {
        color: #333;
    }

    .heatmap-loading,
    .heatmap-error {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        border-radius: 8px;
        z-index: 500;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #007bff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 15px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .heatmap-loading p,
    .heatmap-error p {
        margin: 0;
        font-size: 0.95rem;
        color: #333;
    }

    .heatmap-error {
        background: rgba(255, 200, 200, 0.95);
    }

    .heatmap-error p {
        color: #d32f2f;
        font-weight: 600;
    }

    .geo-heatmap-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        padding: 15px;
        background: rgba(240, 242, 245, 0.8);
        border-radius: 8px;
    }

    .stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background: rgba(255, 255, 255, 0.7);
        border-radius: 6px;
        border-left: 3px solid #007bff;
    }

    .stat-label {
        font-size: 0.85rem;
        color: #666;
        font-weight: 600;
    }

    .stat-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: #007bff;
    }

    @media (max-width: 768px) {
        .geo-heatmap-header {
            flex-direction: column;
            align-items: stretch;
        }

        .heatmap-controls {
            flex-direction: column;
        }

        .control-group {
            flex: 1;
        }

        .control-group .form-control {
            min-width: auto;
            width: 100%;
        }

        .control-group .btn {
            width: 100%;
        }

        .geo-heatmap-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('[data-tenant-id="{{ $tenantId }}"]');
        const tenantId = container.dataset.tenantId;
        const correlationId = container.dataset.correlationId;
        const mapElement = document.getElementById(`geo-heatmap-map-{{ $tenantId }}`);
        
        let map = null;
        let heatmapLayer = null;
        let heatData = [];
        let heatmapService = null;

        // Initialize Leaflet Map
        function initMap() {
            if (!window.L) {
                console.error('Leaflet library not loaded');
                showError('Leaflet библиотека не загружена');
                return;
            }

            map = window.L.map(`geo-heatmap-map-{{ $tenantId }}`).setView([55.7558, 37.6173], 4); // Russia center

            // Add tile layer
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19,
                minZoom: 2
            }).addTo(map);

            // Initialize HeatmapService with real-time updates
            initHeatmapService();

            // Load initial heatmap data
            loadHeatmapData();

            // Event Listeners
            setupEventListeners();
        }

        // Initialize HeatmapService with WebSocket real-time updates
        function initHeatmapService() {
            if (window.HeatmapService) {
                heatmapService = new window.HeatmapService({
                    enableRealtime: true,
                    cacheTtl: 60 * 1000 // 1 minute
                });

                // Listen for heatmap updates
                heatmapService.on('heatmap-updated', (data) => {
                    console.log('[geo-heatmap] Received real-time update:', data);
                    if (data.heatmap_type === 'geo' && data.tenant_id === tenantId) {
                        // Auto-refresh heatmap when data changes
                        loadHeatmapData();
                    }
                });

                // Handle WebSocket connection status
                heatmapService.on('connected', () => {
                    console.log('[geo-heatmap] Real-time updates connected');
                });

                heatmapService.on('disconnected', () => {
                    console.log('[geo-heatmap] Real-time updates disconnected');
                });

                heatmapService.on('error', (error) => {
                    console.warn('[geo-heatmap] Real-time updates error:', error);
                });
            }
        }

        // Load heatmap data from API
        function loadHeatmapData() {
            const fromDate = document.getElementById(`date-range-{{ $tenantId }}`).dataset.startDate;
            const toDate = document.getElementById(`date-range-{{ $tenantId }}`).dataset.endDate;
            const vertical = document.getElementById(`vertical-filter-{{ $tenantId }}`).value;
            const activityType = document.getElementById(`activity-type-{{ $tenantId }}`).value;

            showLoading();

            const params = new URLSearchParams({
                tenant_id: tenantId,
                vertical: vertical,
                from_date: fromDate,
                to_date: toDate,
                activity_type: activityType,
                correlation_id: correlationId
            });

            fetch(`/api/analytics/heatmaps/geo?${params}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    heatData = data.data || [];
                    renderHeatmap(heatData);
                    updateStats(data);
                    hideLoading();
                })
                .catch(error => {
                    console.error('Error loading heatmap:', error);
                    showError(`Ошибка загрузки: ${error.message}`);
                });
        }

        // Render heatmap on the map
        function renderHeatmap(data) {
            if (!window.HeatmapJS) {
                console.warn('Heatmap.js library not loaded, using markers instead');
                renderAsMarkers(data);
                return;
            }

            // Clear existing heatmap
            if (heatmapLayer) {
                map.removeLayer(heatmapLayer);
            }

            // Prepare heatmap data in required format
            const heatmapPoints = data.map(point => [
                point.latitude,
                point.longitude,
                point.weight || point.count || 1
            ]);

            // Create heatmap layer
            heatmapLayer = window.L.heatLayer(heatmapPoints, {
                radius: 25,
                blur: 15,
                maxZoom: 17,
                gradient: {
                    0.0: 'blue',
                    0.25: 'lime',
                    0.5: 'yellow',
                    0.75: 'orange',
                    1.0: 'red'
                }
            }).addTo(map);

            // Auto-fit bounds
            if (heatmapPoints.length > 0) {
                const group = new window.L.featureGroup(
                    heatmapPoints.map(p => window.L.marker([p[0], p[1]]))
                );
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }

        // Fallback rendering with markers
        function renderAsMarkers(data) {
            const maxWeight = Math.max(...data.map(p => p.weight || p.count || 1));

            data.forEach(point => {
                const weight = point.weight || point.count || 1;
                const intensity = weight / maxWeight;

                const color = getColorByIntensity(intensity);
                const marker = window.L.circleMarker(
                    [point.latitude, point.longitude],
                    {
                        radius: 5 + intensity * 10,
                        fillColor: color,
                        color: '#333',
                        weight: 1,
                        opacity: 0.8,
                        fillOpacity: intensity * 0.8
                    }
                );

                marker.bindPopup(`
                    <div style="font-size: 12px;">
                        <p><strong>${point.city || 'Unknown'}</strong></p>
                        <p>Широта: ${point.latitude.toFixed(4)}</p>
                        <p>Долгота: ${point.longitude.toFixed(4)}</p>
                        <p>Активность: ${weight}</p>
                    </div>
                `);

                marker.addTo(map);
            });
        }

        // Get color by intensity (0-1)
        function getColorByIntensity(intensity) {
            if (intensity < 0.25) return '#0000ff'; // Blue
            if (intensity < 0.5) return '#00ff00'; // Green
            if (intensity < 0.75) return '#ffff00'; // Yellow
            return '#ff0000'; // Red
        }

        // Update statistics
        function updateStats(data) {
            const points = document.getElementById(`geo-heatmap-points-{{ $tenantId }}`);
            const max = document.getElementById(`geo-heatmap-max-{{ $tenantId }}`);
            const avg = document.getElementById(`geo-heatmap-avg-{{ $tenantId }}`);
            const cities = document.getElementById(`geo-heatmap-cities-{{ $tenantId }}`);

            const values = (data.data || []).map(p => p.weight || p.count || 1);
            const uniqueCities = new Set((data.data || []).map(p => p.city).filter(c => c));

            points.textContent = values.length;
            max.textContent = values.length > 0 ? Math.max(...values) : 0;
            avg.textContent = values.length > 0 ? (values.reduce((a, b) => a + b, 0) / values.length).toFixed(2) : 0;
            cities.textContent = uniqueCities.size;
        }

        // Setup event listeners
        function setupEventListeners() {
            document.getElementById(`vertical-filter-{{ $tenantId }}`).addEventListener('change', loadHeatmapData);
            document.getElementById(`activity-type-{{ $tenantId }}`).addEventListener('change', loadHeatmapData);
            document.querySelector(`[data-heatmap="geo"].btn-refresh-heatmap`).addEventListener('click', loadHeatmapData);
            
            // Export button handlers
            document.querySelectorAll(`[data-heatmap="geo"].btn-export-heatmap`).forEach(btn => {
                btn.addEventListener('click', exportHeatmap);
            });
        }

        // Export heatmap as PNG or PDF
        function exportHeatmap(event) {
            const format = event.target.closest('button').dataset.format || 'png';
            showLoading();

            // Get heatmap container HTML
            const mapContainer = document.getElementById(`geo-heatmap-map-{{ $tenantId }}`);
            const mapHtml = mapContainer.outerHTML;

            // Prepare export data
            const exportData = {
                tenant_id: tenantId,
                heatmap_html: mapHtml,
                format: format,
                metadata: {
                    title: 'Географическая тепловая карта',
                    vertical: document.getElementById(`vertical-filter-{{ $tenantId }}`).value || 'All',
                    from_date: document.getElementById(`date-range-{{ $tenantId }}`).dataset.startDate,
                    to_date: document.getElementById(`date-range-{{ $tenantId }}`).dataset.endDate,
                    generated_at: new Date().toISOString()
                }
            };

            // Call export API
            const endpoint = `/api/analytics/heatmaps/export/geo/${format}`;
            
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Correlation-ID': correlationId
                },
                body: JSON.stringify(exportData)
            })
            .then(response => {
                hideLoading();
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || `HTTP ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.export && data.export.url) {
                    // Download file via signed URL
                    const link = document.createElement('a');
                    link.href = data.export.url;
                    link.download = data.export.filename || `heatmap-geo.${format}`;
                    link.setAttribute('target', '_blank');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    // Show success message
                    const successMsg = document.createElement('div');
                    successMsg.className = 'export-success-notification';
                    successMsg.textContent = `✓ Экспорт завершен: ${data.export.filename}`;
                    successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #4caf50; color: white; padding: 15px 20px; border-radius: 4px; z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,0.2);';
                    document.body.appendChild(successMsg);
                    setTimeout(() => successMsg.remove(), 5000);
                } else {
                    throw new Error('Invalid export response');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Export error:', error);
                showError(`Ошибка экспорта: ${error.message}`);
            });
        }

        // Helper functions
        function showLoading() {
            document.getElementById(`geo-heatmap-loading-{{ $tenantId }}`).style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById(`geo-heatmap-loading-{{ $tenantId }}`).style.display = 'none';
        }

        function showError(message) {
            const errorEl = document.getElementById(`geo-heatmap-error-{{ $tenantId }}`);
            document.getElementById(`geo-heatmap-error-message`).textContent = message;
            errorEl.style.display = 'flex';
        }

        // Initialize on load
        initMap();

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (heatmapService) {
                heatmapService.destroy();
            }
        });
    });
</script>
