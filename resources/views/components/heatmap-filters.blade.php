@props(['tenantId' => null, 'superAdmin' => false, 'showActivityType' => true, 'showVertical' => true, 'defaultFromDate' => null, 'defaultToDate' => null])

<div class="heatmap-filters-component" data-tenant-id="{{ $tenantId }}">
    <form id="heatmap-filters-form" class="filters-form">
        <div class="filters-grid">
            <!-- Date Range Section -->
            <div class="filter-section">
                <h4 class="section-title">
                    <i class="fas fa-calendar-alt"></i> Период
                </h4>
                
                <div class="date-inputs">
                    <div class="input-group">
                        <label for="date-from">От:</label>
                        <input 
                            type="date" 
                            id="date-from" 
                            name="date_from"
                            value="{{ $defaultFromDate ?? now()->subDays(30)->toDateString() }}"
                            class="form-control date-input"
                        />
                    </div>

                    <div class="input-group">
                        <label for="date-to">До:</label>
                        <input 
                            type="date" 
                            id="date-to" 
                            name="date_to"
                            value="{{ $defaultToDate ?? now()->toDateString() }}"
                            class="form-control date-input"
                        />
                    </div>

                    <!-- Quick presets -->
                    <div class="quick-preset">
                        <button type="button" class="preset-btn" data-days="7">7 дней</button>
                        <button type="button" class="preset-btn" data-days="30">30 дней</button>
                        <button type="button" class="preset-btn" data-days="90">90 дней</button>
                        <button type="button" class="preset-btn" data-days="365">1 год</button>
                    </div>
                </div>
            </div>

            <!-- Vertical/Category Section -->
            @if($showVertical)
            <div class="filter-section">
                <h4 class="section-title">
                    <i class="fas fa-layer-group"></i> Вертикаль
                </h4>
                
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="vertical" value="" class="vertical-checkbox" checked />
                        <span>Все вертикали</span>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="vertical" value="beauty" class="vertical-checkbox" />
                        <span>Красота / Салоны</span>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="vertical" value="food" class="vertical-checkbox" />
                        <span>Еда / Доставка</span>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="vertical" value="auto" class="vertical-checkbox" />
                        <span>Авто / Такси</span>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="vertical" value="hotels" class="vertical-checkbox" />
                        <span>Гостиницы</span>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="vertical" value="realestate" class="vertical-checkbox" />
                        <span>Недвижимость</span>
                    </label>
                </div>
            </div>
            @endif

            <!-- Activity Type Section -->
            @if($showActivityType)
            <div class="filter-section">
                <h4 class="section-title">
                    <i class="fas fa-mouse"></i> Тип активности
                </h4>
                
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="activity_type" value="" class="activity-type-checkbox" checked />
                        <span>Все типы</span>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="activity_type" value="view" class="activity-type-checkbox" />
                        <span>Просмотры</span>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="activity_type" value="click" class="activity-type-checkbox" />
                        <span>Клики</span>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="activity_type" value="purchase" class="activity-type-checkbox" />
                        <span>Покупки</span>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="activity_type" value="booking" class="activity-type-checkbox" />
                        <span>Бронирования</span>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="activity_type" value="order" class="activity-type-checkbox" />
                        <span>Заказы</span>
                    </label>
                </div>
            </div>
            @endif

            <!-- Tenant Filter (Super Admin Only) -->
            @if($superAdmin)
            <div class="filter-section">
                <h4 class="section-title">
                    <i class="fas fa-building"></i> Бизнес / Клиент
                </h4>
                
                <input 
                    type="text" 
                    name="tenant_search"
                    id="tenant-search"
                    class="form-control tenant-search"
                    placeholder="Поиск по названию..."
                />
                
                <div id="tenant-list" class="tenant-list">
                    <div class="loading">Загрузка...</div>
                </div>
            </div>
            @endif

            <!-- Device Type Section -->
            <div class="filter-section">
                <h4 class="section-title">
                    <i class="fas fa-mobile-alt"></i> Устройство
                </h4>
                
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="device_type" value="" class="device-type-checkbox" checked />
                        <span>Все устройства</span>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="device_type" value="mobile" class="device-type-checkbox" />
                        <span>Мобильные</span>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="device_type" value="tablet" class="device-type-checkbox" />
                        <span>Планшеты</span>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="device_type" value="desktop" class="device-type-checkbox" />
                        <span>Десктоп</span>
                    </label>
                </div>
            </div>

            <!-- Advanced Options -->
            <div class="filter-section">
                <h4 class="section-title">
                    <i class="fas fa-sliders-h"></i> Доп. параметры
                </h4>
                
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="anonymized" id="anonymized-toggle" />
                        <span>Только анонимизированные данные</span>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="exclude_bots" id="exclude-bots-toggle" checked />
                        <span>Исключить ботов</span>
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="exclude_internal" id="exclude-internal-toggle" />
                        <span>Исключить внутренние IP</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="filters-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-check"></i> Применить фильтры
            </button>
            
            <button type="reset" class="btn btn-secondary">
                <i class="fas fa-times"></i> Сбросить
            </button>

            <button type="button" class="btn btn-outline" id="save-filter-btn">
                <i class="fas fa-save"></i> Сохранить фильтр
            </button>

            <button type="button" class="btn btn-outline" id="load-filter-btn">
                <i class="fas fa-folder-open"></i> Загрузить фильтр
            </button>
        </div>

        <!-- Applied Filters Display -->
        <div id="applied-filters" class="applied-filters" style="display: none;">
            <h5>Активные фильтры:</h5>
            <div id="filters-tags" class="filters-tags">
                <!-- Tags will be added here dynamically -->
            </div>
        </div>
    </form>
</div>

<style>
    .heatmap-filters-component {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 20px;
    }

    .filters-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .filter-section {
        background: rgba(245, 247, 250, 0.5);
        padding: 15px;
        border-radius: 8px;
        border: 1px solid rgba(200, 210, 230, 0.3);
    }

    .section-title {
        margin: 0 0 12px 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #1a1a1a;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-title i {
        color: #007bff;
        font-size: 0.9rem;
    }

    .date-inputs {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .input-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .input-group label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #333;
    }

    .date-input {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.9rem;
    }

    .date-input:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }

    .quick-preset {
        display: flex;
        gap: 8px;
        margin-top: 10px;
    }

    .preset-btn {
        flex: 1;
        padding: 6px 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: white;
        cursor: pointer;
        font-size: 0.8rem;
        font-weight: 600;
        color: #666;
        transition: all 0.2s ease;
    }

    .preset-btn:hover {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }

    .preset-btn.active {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }

    .checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 0.9rem;
        color: #333;
        transition: all 0.2s ease;
    }

    .checkbox-label input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #007bff;
    }

    .checkbox-label:hover {
        color: #007bff;
    }

    .tenant-search {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.9rem;
        margin-bottom: 10px;
    }

    .tenant-search:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }

    .tenant-list {
        max-height: 250px;
        overflow-y: auto;
    }

    .tenant-list .loading {
        text-align: center;
        color: #999;
        padding: 10px;
        font-size: 0.85rem;
    }

    .filters-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .btn {
        padding: 10px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
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

    .btn-outline {
        background: white;
        color: #007bff;
        border: 1px solid #007bff;
    }

    .btn-outline:hover {
        background: #f0f8ff;
    }

    .applied-filters {
        padding: 12px;
        background: rgba(0, 123, 255, 0.05);
        border-radius: 6px;
        border-left: 3px solid #007bff;
    }

    .applied-filters h5 {
        margin: 0 0 10px 0;
        font-size: 0.9rem;
        font-weight: 700;
        color: #007bff;
    }

    .filters-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .filter-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        background: #007bff;
        color: white;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .filter-tag .remove {
        cursor: pointer;
        font-size: 0.9rem;
    }

    .filter-tag .remove:hover {
        opacity: 0.8;
    }

    @media (max-width: 768px) {
        .heatmap-filters-component {
            padding: 15px;
        }

        .filters-grid {
            grid-template-columns: 1fr;
        }

        .filters-actions {
            justify-content: stretch;
        }

        .filters-actions .btn {
            flex: 1;
            justify-content: center;
        }

        .quick-preset {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('heatmap-filters-form');
        
        // Quick date presets
        document.querySelectorAll('.preset-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const days = parseInt(this.dataset.days);
                const from = new Date();
                from.setDate(from.getDate() - days);
                
                document.getElementById('date-from').value = from.toISOString().split('T')[0];
                document.getElementById('date-to').value = new Date().toISOString().split('T')[0];
                
                // Mark as active
                document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // "All" checkbox behavior
        document.querySelectorAll('[name="vertical"], [name="activity_type"], [name="device_type"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const name = this.name;
                const checkboxes = document.querySelectorAll(`[name="${name}"]`);
                const allCheckbox = Array.from(checkboxes).find(c => c.value === '');
                
                if (this.value === '') {
                    // If "All" is clicked, uncheck others
                    checkboxes.forEach(c => {
                        if (c.value !== '') c.checked = false;
                    });
                } else {
                    // If any specific option is clicked, uncheck "All"
                    if (allCheckbox) allCheckbox.checked = false;
                }
            });
        });

        // Form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            displayAppliedFilters();
            // Trigger heatmap update - emit custom event
            window.dispatchEvent(new CustomEvent('heatmap-filters-applied', {
                detail: getFilterValues()
            }));
        });

        // Get filter values as object
        function getFilterValues() {
            const formData = new FormData(form);
            const filters = {
                date_from: formData.get('date_from'),
                date_to: formData.get('date_to'),
                verticals: Array.from(formData.getAll('vertical')).filter(v => v !== ''),
                activity_types: Array.from(formData.getAll('activity_type')).filter(a => a !== ''),
                device_types: Array.from(formData.getAll('device_type')).filter(d => d !== ''),
                anonymized: formData.get('anonymized') ? true : false,
                exclude_bots: formData.get('exclude_bots') ? true : false,
                exclude_internal: formData.get('exclude_internal') ? true : false
            };
            return filters;
        }

        // Display applied filters
        function displayAppliedFilters() {
            const filters = getFilterValues();
            const tags = [];

            if (filters.date_from || filters.date_to) {
                tags.push(`📅 ${filters.date_from} → ${filters.date_to}`);
            }
            if (filters.verticals.length > 0) {
                tags.push(`📊 Вертикали: ${filters.verticals.join(', ')}`);
            }
            if (filters.activity_types.length > 0) {
                tags.push(`🖱️ Типы: ${filters.activity_types.join(', ')}`);
            }
            if (filters.device_types.length > 0) {
                tags.push(`📱 Устройства: ${filters.device_types.join(', ')}`);
            }
            if (filters.anonymized) {
                tags.push(`🔒 Только анонимные`);
            }
            if (filters.exclude_bots) {
                tags.push(`🤖 Без ботов`);
            }

            const appliedEl = document.getElementById('applied-filters');
            const tagsEl = document.getElementById('filters-tags');

            if (tags.length > 0) {
                tagsEl.innerHTML = tags.map(tag => `<span class="filter-tag">${tag}</span>`).join('');
                appliedEl.style.display = 'block';
            } else {
                appliedEl.style.display = 'none';
            }
        }

        // Save filter to localStorage
        document.getElementById('save-filter-btn').addEventListener('click', function() {
            const name = prompt('Введите название фильтра:');
            if (name) {
                localStorage.setItem(`heatmap-filter-${name}`, JSON.stringify(getFilterValues()));
                alert(`Фильтр "${name}" сохранён`);
            }
        });

        // Load filter from localStorage
        document.getElementById('load-filter-btn').addEventListener('click', function() {
            const name = prompt('Введите название сохранённого фильтра:');
            if (name) {
                const saved = localStorage.getItem(`heatmap-filter-${name}`);
                if (saved) {
                    const filters = JSON.parse(saved);
                    // Apply filters to form
                    document.getElementById('date-from').value = filters.date_from;
                    document.getElementById('date-to').value = filters.date_to;
                    form.dispatchEvent(new Event('submit'));
                    alert(`Фильтр "${name}" загружен`);
                } else {
                    alert(`Фильтр "${name}" не найден`);
                }
            }
        });
    });
</script>
