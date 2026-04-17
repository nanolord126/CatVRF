<template>
  <div class="fraud-detection-panel">
    <div class="tabs">
      <button
        @click="activeTab = 'serial'"
        :class="{ active: activeTab === 'serial' }"
        class="tab-button"
      >
        Валидация серийного номера
      </button>
      <button
        @click="activeTab = 'return'"
        :class="{ active: activeTab === 'return' }"
        class="tab-button"
      >
        Детекция мошенничества при возврате
      </button>
      <button
        @click="activeTab = 'statistics'"
        :class="{ active: activeTab === 'statistics' }"
        class="tab-button"
      >
        Статистика
      </button>
    </div>

    <div class="tab-content">
      <SerialNumberValidation v-if="activeTab === 'serial'" />
      <ReturnFraudDetection v-if="activeTab === 'return'" />
      <FraudStatistics v-if="activeTab === 'statistics'" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import SerialNumberValidation from './SerialNumberValidation.vue';
import ReturnFraudDetection from './ReturnFraudDetection.vue';
import FraudStatistics from './FraudStatistics.vue';

const activeTab = ref('serial');
</script>

<style scoped>
.fraud-detection-panel {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.tabs {
  display: flex;
  gap: 4px;
  margin-bottom: 20px;
  border-bottom: 2px solid #e5e7eb;
}

.tab-button {
  padding: 12px 24px;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  margin-bottom: -2px;
}

.tab-button:hover {
  color: #3b82f6;
}

.tab-button.active {
  color: #3b82f6;
  border-bottom-color: #3b82f6;
}

.tab-content {
  background: white;
  border-radius: 8px;
  padding: 20px;
}
</style>
