<template>
  <div class="dynamic-pricing-settings">
    <div class="settings-header">
      <h3>Настройки динамического ценообразования</h3>
      <div class="toggle-switch">
        <label class="switch">
          <input 
            type="checkbox" 
            v-model="settings.enabled"
            @change="onToggle"
          >
          <span class="slider"></span>
        </label>
        <span class="toggle-label">{{ settings.enabled ? 'Включено' : 'Выключено' }}</span>
      </div>
    </div>

    <div v-if="settings.enabled" class="settings-content">
      <div class="setting-group">
        <label class="setting-label">
          Минимальная цена
          <span class="setting-hint">Если не задано = базовая цена</span>
        </label>
        <div class="input-wrapper">
          <input 
            type="number" 
            v-model.number="settings.min_price"
            class="price-input"
            placeholder="Авто (базовая цена)"
            min="0"
          >
          <span class="input-suffix">₽</span>
        </div>
      </div>

      <div class="setting-group">
        <label class="setting-label">
          Максимальная цена
          <span class="setting-hint">Если не задано = базовая + 50%</span>
        </label>
        <div class="input-wrapper">
          <input 
            type="number" 
            v-model.number="settings.max_price"
            class="price-input"
            placeholder="Авто (базовая + 50%)"
            min="0"
          >
          <span class="input-suffix">₽</span>
        </div>
      </div>

      <div class="setting-group">
        <label class="setting-label">
          Маржа платформы от роста цены
          <span class="setting-hint">По умолчанию 30%</span>
        </label>
        <div class="input-wrapper">
          <input 
            type="number" 
            v-model.number="settings.platform_margin"
            class="price-input"
            placeholder="30"
            min="0"
            max="100"
          >
          <span class="input-suffix">%</span>
        </div>
      </div>

      <div class="preview-section">
        <h4>Пример расчёта</h4>
        <div class="preview-card">
          <div class="preview-row">
            <span class="preview-label">Базовая цена:</span>
            <span class="preview-value">{{ example.base_price }}₽</span>
          </div>
          <div class="preview-row">
            <span class="preview-label">Динамическая цена:</span>
            <span class="preview-value dynamic">{{ example.dynamic_price }}₽</span>
          </div>
          <div class="preview-row highlight">
            <span class="preview-label">Рост цены:</span>
            <span class="preview-value increase">+{{ example.price_increase }}₽</span>
          </div>
          <div class="preview-row highlight">
            <span class="preview-label">Маржа платформы:</span>
            <span class="preview-value margin">{{ example.platform_margin }}₽</span>
          </div>
          <div class="preview-row final">
            <span class="preview-label">Получит продавец:</span>
            <span class="preview-value seller">{{ example.seller_amount }}₽</span>
          </div>
        </div>
      </div>

      <div class="factors-info">
        <h4>Факторы влияния на цену</h4>
        <div class="factors-grid">
          <div class="factor-card">
            <TrendingUp class="factor-icon" />
            <span class="factor-name">Спрос</span>
            <span class="factor-weight">30%</span>
          </div>
          <div class="factor-card">
            <Package class="factor-icon" />
            <span class="factor-name">Остатки</span>
            <span class="factor-weight">25%</span>
          </div>
          <div class="factor-card">
            <Clock class="factor-icon" />
            <span class="factor-name">Время</span>
            <span class="factor-weight">10%</span>
          </div>
          <div class="factor-card">
            <Calendar class="factor-icon" />
            <span class="factor-name">Сезонность</span>
            <span class="factor-weight">20%</span>
          </div>
          <div class="factor-card">
            <BarChart class="factor-icon" />
            <span class="factor-name">Конкуренция</span>
            <span class="factor-weight">15%</span>
          </div>
        </div>
      </div>

      <div class="settings-actions">
        <button @click="saveSettings" class="save-btn">
          Сохранить настройки
        </button>
        <button @click="resetSettings" class="reset-btn">
          Сбросить
        </button>
      </div>
    </div>

    <div v-else class="disabled-state">
      <div class="disabled-icon">
        <Settings class="icon" />
      </div>
      <p class="disabled-text">
        Динамическое ценообразование выключено. Цена не будет меняться автоматически.
      </p>
      <p class="disabled-hint">
        При выключении цена остаётся на базовом уровне. При включении цена может расти
        в зависимости от спроса, остатков и других факторов. Разница роста остаётся платформе.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Settings, TrendingUp, Package, Clock, Calendar, BarChart } from 'lucide-vue-next'

interface PricingSettings {
  enabled: boolean
  min_price?: number
  max_price?: number
  platform_margin: number
}

const props = defineProps<{
  basePrice: number
  initialSettings?: Partial<PricingSettings>
}>()

const emit = defineEmits<{
  save: [settings: PricingSettings]
  toggle: [enabled: boolean]
}>()

const settings = ref<PricingSettings>({
  enabled: false,
  platform_margin: 30,
  ...props.initialSettings,
})

const example = computed(() => {
  const basePrice = props.basePrice
  const dynamicPrice = Math.round(basePrice * 1.15) // Пример: +15% от факторов
  const priceIncrease = dynamicPrice - basePrice
  const platformMargin = Math.round(priceIncrease * (settings.value.platform_margin / 100))
  const sellerAmount = basePrice + priceIncrease - platformMargin
  
  return {
    base_price: basePrice,
    dynamic_price: dynamicPrice,
    price_increase: priceIncrease,
    platform_margin: platformMargin,
    seller_amount: sellerAmount,
  }
})

const onToggle = () => {
  emit('toggle', settings.value.enabled)
}

const saveSettings = () => {
  emit('save', settings.value)
}

const resetSettings = () => {
  settings.value = {
    enabled: false,
    platform_margin: 30,
  }
}
</script>

<style scoped>
.dynamic-pricing-settings {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.settings-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.settings-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
}

.toggle-switch {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.switch {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 24px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #d1d5db;
  transition: 0.3s;
  border-radius: 24px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

input:checked + .slider {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

input:checked + .slider:before {
  transform: translateX(24px);
}

.toggle-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.settings-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.setting-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.setting-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.setting-hint {
  display: block;
  font-size: 0.75rem;
  color: #6b7280;
  font-weight: 400;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.price-input {
  width: 100%;
  padding: 0.625rem 3rem 0.625rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-family: inherit;
}

.price-input:focus {
  outline: none;
  border-color: #22c55e;
  box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
}

.input-suffix {
  position: absolute;
  right: 0.75rem;
  color: #6b7280;
  font-size: 0.875rem;
}

.preview-section {
  background: #f9fafb;
  border-radius: 0.75rem;
  padding: 1rem;
}

.preview-section h4 {
  margin: 0 0 1rem 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: #111827;
}

.preview-card {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.preview-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
}

.preview-label {
  color: #6b7280;
}

.preview-value {
  font-weight: 600;
  color: #111827;
}

.preview-row.highlight .preview-value {
  color: #22c55e;
}

.preview-row.final {
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px solid #e5e7eb;
}

.preview-row.final .preview-value {
  font-size: 1rem;
  color: #16a34a;
}

.factors-info {
  background: #f9fafb;
  border-radius: 0.75rem;
  padding: 1rem;
}

.factors-info h4 {
  margin: 0 0 1rem 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: #111827;
}

.factors-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 0.75rem;
}

.factor-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.375rem;
  padding: 0.75rem;
  background: white;
  border-radius: 0.5rem;
  text-align: center;
}

.factor-icon {
  width: 1.5rem;
  height: 1.5rem;
  color: #6b7280;
}

.factor-name {
  font-size: 0.75rem;
  color: #374151;
}

.factor-weight {
  font-size: 0.625rem;
  color: #22c55e;
  font-weight: 600;
}

.settings-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1rem;
}

.save-btn {
  flex: 1;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.save-btn:hover {
  background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(34, 197, 94, 0.3);
}

.reset-btn {
  padding: 0.75rem 1.5rem;
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.reset-btn:hover {
  background: #e5e7eb;
}

.disabled-state {
  text-align: center;
  padding: 3rem 1rem;
}

.disabled-icon {
  width: 4rem;
  height: 4rem;
  margin: 0 auto 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f3f4f6;
  border-radius: 50%;
}

.disabled-icon .icon {
  width: 2rem;
  height: 2rem;
  color: #9ca3af;
}

.disabled-text {
  font-size: 1rem;
  color: #374151;
  margin: 0 0 0.5rem 0;
}

.disabled-hint {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
  max-width: 400px;
  margin: 0 auto;
}
</style>
