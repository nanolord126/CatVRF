@props(['pageUrl' => null, 'fromDate' => null, 'toDate' => null, 'height' => '600px', 'correlationId' => null])

<div class="click-heatmap-container" data-page-url="{{ $pageUrl }}" data-correlation-id="{{ $correlationId ?? \Illuminate\Support\Str::uuid() }}">
    <!-- Header with Filters -->
    <div class="click-heatmap-header">
        <div class="heatmap-title-section">
            <h3 class="heatmap-title">Тепловая карта кликов пользователей</h3>
            <p class="heatmap-subtitle">Визуализация кликов на странице {{ $pageUrl ?? 'страницы' }}</p>
        </div>

        <div class="heatmap-controls">
            <!-- Page URL Filter -->
            <div class="control-group">
                <label for="page-url-filter">Страница:</label>
                <input 
                    type="text" 
                    id="page-url-filter" 
                    class="form-control page-url-input" 
                    placeholder="/путь/на/странице"
                    value="{{ $pageUrl ?? '' }}"
                />
            </div>

            <!-- Date Range Filter -->
            <div class="control-group">
                <label for="date-range">Период:</label>
                <input 
                    type="text" 
                    id="date-range" 
                    class="form-control date-range-picker" 
                    placeholder="Выберите диапазон дат"
                    data-start-date="{{ $fromDate ?? now()->subDays(7)->toDateString() }}"
                    data-end-date="{{ $toDate ?? now()->toDateString() }}"
                />
            </div>

            <!-- Device Type Filter -->
            <div class="control-group">
                <label for="device-type-filter">Устройство:</label>
                <select id="device-type-filter" class="form-control">
                    <option value="">Все устройства</option>
                    <option value="mobile">Мобильные</option>
                    <option value="tablet">Планшеты</option>
                    <option value="desktop">Десктоп</option>
                </select>
            </div>

            <!-- Refresh Button -->
            <div class="control-group">
                <button class="btn btn-primary btn-refresh">
                    <i class="fas fa-sync-alt"></i> Обновить
                </button>
            </div>

            <!-- Export Buttons -->
            <div class="control-group">
                <button class="btn btn-success btn-export" data-format="png" title="Экспортировать как PNG">
                    <i class="fas fa-image"></i> PNG
                </button>
            </div>
            <div class="control-group">
                <button class="btn btn-info btn-export" data-format="pdf" title="Экспортировать как PDF">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Heatmap Canvas Container -->
    <div class="click-heatmap-wrapper">
        <!-- Screenshot Container -->
        <div class="screenshot-container" id="screenshot-container">
            <img 
                id="page-screenshot" 
                class="page-screenshot" 
                src="" 
                alt="Скриншот страницы"
                style="display: none;"
            />
            
            <!-- Heatmap Canvas Overlay -->
            <canvas 
                id="click-heatmap-canvas" 
                class="click-heatmap-canvas"
                style="display: none;"
            ></canvas>

            <!-- Placeholder -->
            <div id="heatmap-placeholder" class="heatmap-placeholder">
                <p>Загрузите данные кликов</p>
                <small>Укажите страницу и период для начала</small>
            </div>
        </div>

        <!-- Intensity Slider -->
        <div class="heatmap-intensity-control">
            <label for="intensity-slider">Интенсивность наложения:</label>
            <input 
                type="range" 
                id="intensity-slider" 
                min="0" 
                max="100" 
                value="50" 
                class="slider"
            />
            <span id="intensity-value">50%</span>
        </div>

        <!-- Legend -->
        <div class="heatmap-legend">
            <h4 class="legend-title">Плотность кликов</h4>
            <div class="legend-items">
                <div class="legend-item">
                    <span class="legend-color" style="background: #0000ff; opacity: 0.3;"></span>
                    <span class="legend-label">Низкая</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: #00ff00; opacity: 0.6;"></span>
                    <span class="legend-label">Средняя</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: #ff0000; opacity: 1;"></span>
                    <span class="legend-label">Высокая</span>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div class="heatmap-loading" id="click-heatmap-loading" style="display: none;">
            <div class="spinner"></div>
            <p>Загрузка данных кликов...</p>
        </div>

        <!-- Error Message -->
        <div class="heatmap-error" id="click-heatmap-error" style="display: none;">
            <p id="click-heatmap-error-message">Ошибка при загрузке данных</p>
        </div>
    </div>

    <!-- Statistics Panel -->
    <div class="click-heatmap-stats">
        <div class="stat-item">
            <span class="stat-label">Всего кликов:</span>
            <span class="stat-value" id="total-clicks">0</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Уникальных пользователей:</span>
            <span class="stat-value" id="unique-users">0</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Средний клик/пользователь:</span>
            <span class="stat-value" id="avg-clicks-per-user">0</span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Самый кликаемый элемент:</span>
            <span class="stat-value" id="most-clicked-element">-</span>
        </div>
    </div>

    <!-- Heatmap Details Table -->
    <div class="click-heatmap-details">
        <h4>Детали кликов</h4>
        <div class="table-responsive">
            <table class="details-table">
                <thead>
                    <tr>
                        <th>CSS Селектор</th>
                        <th>Кликов</th>
                        <th>Процент</th>
                        <th>Браузер</th>
                        <th>Устройство</th>
                    </tr>
                </thead>
                <tbody id="details-tbody">
                    <tr>
                        <td colspan="5" style="text-align: center; color: #999;">Данные отсутствуют</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .click-heatmap-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 20px;
    }

    .click-heatmap-header {
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

    .control-group input,
    .control-group select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.9rem;
        min-width: 150px;
    }

    .control-group input:focus,
    .control-group select:focus {
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

    .click-heatmap-wrapper {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 20px;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }

    .screenshot-container {
        position: relative;
        width: 100%;
        min-height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
    }

    .page-screenshot {
        max-width: 100%;
        max-height: 800px;
        object-fit: contain;
    }

    .click-heatmap-canvas {
        position: absolute;
        top: 0;
        left: 0;
        cursor: crosshair;
    }

    .heatmap-placeholder {
        text-align: center;
        padding: 40px;
        color: #999;
    }

    .heatmap-placeholder p {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .heatmap-placeholder small {
        font-size: 0.9rem;
        display: block;
        margin-top: 5px;
    }

    .heatmap-intensity-control {
        padding: 15px;
        background: rgba(240, 242, 245, 0.8);
        display: flex;
        align-items: center;
        gap: 15px;
        border-bottom: 1px solid #ddd;
    }

    .heatmap-intensity-control label {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 600;
        color: #333;
    }

    .slider {
        flex: 1;
        min-width: 200px;
        height: 6px;
        border-radius: 3px;
        background: #ddd;
        outline: none;
        -webkit-appearance: none;
    }

    .slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #007bff;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .slider::-webkit-slider-thumb:hover {
        background: #0056b3;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
    }

    .slider::-moz-range-thumb {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #007bff;
        cursor: pointer;
        border: none;
        transition: all 0.3s ease;
    }

    .slider::-moz-range-thumb:hover {
        background: #0056b3;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
    }

    #intensity-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #007bff;
        min-width: 40px;
        text-align: right;
    }

    .heatmap-legend {
        position: absolute;
        bottom: 100px;
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

    .click-heatmap-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        padding: 15px;
        background: rgba(240, 242, 245, 0.8);
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background: rgba(255, 255, 255, 0.7);
        border-radius: 6px;
        border-left: 3px solid #ff6b6b;
    }

    .stat-label {
        font-size: 0.85rem;
        color: #666;
        font-weight: 600;
    }

    .stat-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: #ff6b6b;
    }

    .click-heatmap-details {
        background: rgba(255, 255, 255, 0.7);
        border-radius: 8px;
        padding: 15px;
    }

    .click-heatmap-details h4 {
        margin: 0 0 15px 0;
        font-size: 1rem;
        font-weight: 700;
        color: #1a1a1a;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .details-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .details-table thead {
        background: rgba(0, 0, 0, 0.05);
        border-bottom: 2px solid #ddd;
    }

    .details-table th {
        padding: 10px;
        text-align: left;
        font-weight: 700;
        color: #333;
    }

    .details-table td {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }

    .details-table tbody tr:hover {
        background: rgba(0, 123, 255, 0.05);
    }

    @media (max-width: 768px) {
        .click-heatmap-header {
            flex-direction: column;
            align-items: stretch;
        }

        .heatmap-controls {
            flex-direction: column;
        }

        .control-group {
            flex: 1;
        }

        .control-group input,
        .control-group select {
            min-width: auto;
            width: 100%;
        }

        .control-group .btn {
            width: 100%;
        }

        .click-heatmap-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .heatmap-intensity-control {
            flex-direction: column;
            align-items: stretch;
        }

        .slider {
            min-width: auto;
        }

        .details-table {
            font-size: 0.8rem;
        }

        .details-table th,
        .details-table td {
            padding: 6px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('[data-page-url]');
        const pageUrl = container.dataset.pageUrl;
        const correlationId = container.dataset.correlationId;
        
        let clickData = [];
        let canvas = null;
        let canvasCtx = null;
        let heatmapService = null;

        // Initialize canvas
        function initCanvas() {
            canvas = document.getElementById('click-heatmap-canvas');
            canvasCtx = canvas.getContext('2d');

            // Get screenshot dimensions
            const img = document.getElementById('page-screenshot');
            if (img && img.src) {
                img.onload = function() {
                    canvas.width = img.width;
                    canvas.height = img.height;
                    canvas.style.display = 'block';
                    drawHeatmap();
                };
            }
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
                    console.log('[click-heatmap] Received real-time update:', data);
                    if (data.heatmap_type === 'click') {
                        // Auto-refresh heatmap when data changes
                        loadHeatmapData();
                    }
                });

                // Handle WebSocket connection status
                heatmapService.on('connected', () => {
                    console.log('[click-heatmap] Real-time updates connected');
                });

                heatmapService.on('disconnected', () => {
                    console.log('[click-heatmap] Real-time updates disconnected');
                });

                heatmapService.on('error', (error) => {
                    console.warn('[click-heatmap] Real-time updates error:', error);
                });
            }
        }

        // Load heatmap data from API
        function loadHeatmapData() {
            const url = document.getElementById('page-url-filter').value || pageUrl;
            const fromDate = document.getElementById('date-range').dataset.startDate;
            const toDate = document.getElementById('date-range').dataset.endDate;
            const deviceType = document.getElementById('device-type-filter').value;

            if (!url) {
                showError('Укажите URL страницы');
                return;
            }

            showLoading();

            const params = new URLSearchParams({
                page_url: url,
                from_date: fromDate,
                to_date: toDate,
                device_type: deviceType,
                correlation_id: correlationId
            });

            fetch(`/api/analytics/heatmaps/click?${params}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    clickData = data.data || [];
                    updateStats(data);
                    updateDetailsTable(clickData);
                    loadScreenshot(url);
                    hideLoading();
                })
                .catch(error => {
                    console.error('Error loading heatmap:', error);
                    showError(`Ошибка загрузки: ${error.message}`);
                });
        }

        // Load page screenshot from server
        function loadScreenshot(url) {
            const screenshotUrl = `/api/analytics/heatmaps/click/screenshot?url=${encodeURIComponent(url)}&correlation_id=${correlationId}`;
            const img = document.getElementById('page-screenshot');
            
            // Show loading while fetching screenshot
            const placeholder = document.getElementById('heatmap-placeholder');
            placeholder.innerHTML = '<div class="spinner"></div><p>Загрузка скриншота страницы...</p>';
            placeholder.style.display = 'flex';
            
            img.onerror = function() {
                console.warn('Could not load screenshot from server, showing placeholder');
                placeholder.innerHTML = '<p>⚠️ Скриншот страницы недоступен</p><small>Используется визуализация только данных кликов</small>';
                placeholder.style.display = 'flex';
                img.style.display = 'none';
            };
            
            img.onload = function() {
                placeholder.style.display = 'none';
                img.style.display = 'block';
                initCanvas();
            };
            
            img.src = screenshotUrl;
        }

        // Draw heatmap on canvas
        function drawHeatmap() {
            if (!canvas || !canvasCtx || clickData.length === 0) return;

            const intensity = parseInt(document.getElementById('intensity-slider').value) / 100;

            // Clear canvas
            canvasCtx.clearRect(0, 0, canvas.width, canvas.height);

            // Calculate max clicks for normalization
            const maxClicks = Math.max(...clickData.map(p => p.count || 1));

            // Draw heat points
            clickData.forEach(point => {
                const normalizedIntensity = (point.count || 1) / maxClicks;
                const color = getColorByIntensity(normalizedIntensity);
                const radius = 10 + normalizedIntensity * 30;
                const opacity = normalizedIntensity * intensity;

                // Draw gradient circle
                const gradient = canvasCtx.createRadialGradient(point.x, point.y, 0, point.x, point.y, radius);
                gradient.addColorStop(0, `rgba(${hexToRgb(color)}, ${opacity})`);
                gradient.addColorStop(1, `rgba(${hexToRgb(color)}, 0)`);

                canvasCtx.fillStyle = gradient;
                canvasCtx.beginPath();
                canvasCtx.arc(point.x, point.y, radius, 0, 2 * Math.PI);
                canvasCtx.fill();
            });
        }

        // Get color by intensity (0-1)
        function getColorByIntensity(intensity) {
            if (intensity < 0.25) return '#0000ff'; // Blue
            if (intensity < 0.5) return '#00ff00'; // Green
            if (intensity < 0.75) return '#ffff00'; // Yellow
            return '#ff0000'; // Red
        }

        // Convert hex to RGB
        function hexToRgb(hex) {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : '0, 0, 0';
        }

        // Update statistics
        function updateStats(data) {
            const totalClicks = (data.data || []).reduce((sum, p) => sum + (p.count || 1), 0);
            const uniqueUsers = new Set((data.data || []).map(p => p.user_id).filter(u => u)).size;
            const avgClicks = uniqueUsers > 0 ? (totalClicks / uniqueUsers).toFixed(2) : 0;

            document.getElementById('total-clicks').textContent = totalClicks;
            document.getElementById('unique-users').textContent = uniqueUsers;
            document.getElementById('avg-clicks-per-user').textContent = avgClicks;

            // Find most clicked element
            if (data.data && data.data.length > 0) {
                const mostClicked = data.data.reduce((max, p) => 
                    (p.count || 1) > (max.count || 1) ? p : max
                );
                document.getElementById('most-clicked-element').textContent = 
                    mostClicked.element_selector || mostClicked.selector || '-';
            }
        }

        // Update details table
        function updateDetailsTable(data) {
            const tbody = document.getElementById('details-tbody');
            
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #999;">Данные отсутствуют</td></tr>';
                return;
            }

            const totalClicks = data.reduce((sum, p) => sum + (p.count || 1), 0);

            tbody.innerHTML = data.slice(0, 10).map(row => `
                <tr>
                    <td title="${row.element_selector || row.selector || '-'}" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis;">
                        ${(row.element_selector || row.selector || '-').substring(0, 40)}...
                    </td>
                    <td>${row.count || 1}</td>
                    <td>${((row.count || 1) / totalClicks * 100).toFixed(1)}%</td>
                    <td>${row.browser || '-'}</td>
                    <td>${row.device_type || '-'}</td>
                </tr>
            `).join('');
        }

        // Setup event listeners
        function setupEventListeners() {
            // Initialize HeatmapService for real-time updates
            initHeatmapService();

            document.getElementById('page-url-filter').addEventListener('change', loadHeatmapData);
            document.getElementById('device-type-filter').addEventListener('change', loadHeatmapData);
            document.querySelector('.btn-refresh').addEventListener('click', loadHeatmapData);
            
            document.getElementById('intensity-slider').addEventListener('input', function(e) {
                document.getElementById('intensity-value').textContent = e.target.value + '%';
                drawHeatmap();
            });

            // Export button handlers
            document.querySelectorAll('.btn-export').forEach(btn => {
                btn.addEventListener('click', exportHeatmap);
            });
        }

        // Export heatmap as PNG or PDF
        function exportHeatmap(event) {
            const format = event.target.closest('button').dataset.format || 'png';
            showLoading();

            // Convert canvas to base64 data URL
            let canvasDataUrl = null;
            if (canvas && canvas.style.display !== 'none') {
                canvasDataUrl = canvas.toDataURL('image/png');
            }

            // Prepare export data
            const exportData = {
                tenant_id: document.querySelector('[data-page-url]').dataset.tenantId || 'unknown',
                canvas_data_url: canvasDataUrl,
                page_url: document.getElementById('page-url-filter').value || pageUrl,
                format: format,
                metadata: {
                    title: 'Тепловая карта кликов',
                    page_url: document.getElementById('page-url-filter').value || pageUrl,
                    from_date: document.getElementById('date-range').dataset.startDate,
                    to_date: document.getElementById('date-range').dataset.endDate,
                    device_type: document.getElementById('device-type-filter').value || 'All',
                    generated_at: new Date().toISOString()
                }
            };

            // Call export API
            const endpoint = `/api/analytics/heatmaps/export/click/${format}`;
            
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
                    link.download = data.export.filename || `heatmap-click.${format}`;
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
            document.getElementById('click-heatmap-loading').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('click-heatmap-loading').style.display = 'none';
        }

        function showError(message) {
            const errorEl = document.getElementById('click-heatmap-error');
            document.getElementById('click-heatmap-error-message').textContent = message;
            errorEl.style.display = 'flex';
            setTimeout(() => { errorEl.style.display = 'none'; }, 5000);
        }

        // Initialize
        initCanvas();
        setupEventListeners();

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (heatmapService) {
                heatmapService.destroy();
            }
        });
    });
</script>
