<!-- Filter Persistence Helper - uses localStorage -->
<script>
    /**
     * Функции для работы с фильтрами в localStorage
     * 
     * saveFilter(key, value) - сохранить фильтр
     * loadFilter(key) - загрузить фильтр
     * clearFilters() - очистить все фильтры
     */
    window.FilterPersistence = {
        storageKey: 'analytics_filters',
        
        /**
         * Сохранить фильтр
         */
        saveFilter(key, value) {
            try {
                let filters = this.loadAllFilters();
                filters[key] = value;
                localStorage.setItem(this.storageKey, JSON.stringify(filters));
                console.log('✅ Filter saved:', key, value);
            } catch (e) {
                console.error('❌ Failed to save filter:', e);
            }
        },
        
        /**
         * Загрузить фильтр
         */
        loadFilter(key, defaultValue = null) {
            try {
                let filters = this.loadAllFilters();
                return filters[key] !== undefined ? filters[key] : defaultValue;
            } catch (e) {
                console.error('❌ Failed to load filter:', e);
                return defaultValue;
            }
        },
        
        /**
         * Загрузить все фильтры
         */
        loadAllFilters() {
            try {
                const data = localStorage.getItem(this.storageKey);
                return data ? JSON.parse(data) : {};
            } catch (e) {
                console.error('❌ Failed to parse filters:', e);
                return {};
            }
        },
        
        /**
         * Очистить все фильтры
         */
        clearFilters() {
            try {
                localStorage.removeItem(this.storageKey);
                console.log('✅ Filters cleared');
            } catch (e) {
                console.error('❌ Failed to clear filters:', e);
            }
        },
        
        /**
         * Применить сохранённые фильтры к Livewire компоненту
         */
        applyToComponent(component) {
            const filters = this.loadAllFilters();
            Object.keys(filters).forEach(key => {
                if (component[key] !== undefined) {
                    component[key] = filters[key];
                    console.log('📥 Applied filter:', key, '=', filters[key]);
                }
            });
        },
    };

    // Слушать изменения фильтров в Livewire
    document.addEventListener('livewire:updated', (event) => {
        // Сохранить текущие значения фильтров
        const component = event.detail.component;
        if (component && component.vertical) {
            window.FilterPersistence.saveFilter('vertical', component.vertical);
        }
        if (component && component.heatmapType) {
            window.FilterPersistence.saveFilter('heatmapType', component.heatmapType);
        }
        if (component && component.aggregation) {
            window.FilterPersistence.saveFilter('aggregation', component.aggregation);
        }
    });

    // При загрузке страницы - восстановить фильтры
    window.addEventListener('load', () => {
        const filters = window.FilterPersistence.loadAllFilters();
        if (Object.keys(filters).length > 0) {
            console.log('🔄 Restoring filters:', filters);
        }
    });
</script>
