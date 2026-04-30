<template>
  <div class="streak-display">
    <div class="streak-header">
      <h3>🔥 Activity Streak</h3>
      <button @click="refreshStreak" class="refresh-btn" :disabled="loading">
        🔄
      </button>
    </div>

    <div v-if="loading" class="loading">
      Loading streak...
    </div>

    <div v-else-if="error" class="error">
      {{ error }}
    </div>

    <div v-else class="streak-content">
      <div class="streak-main" :style="{ borderColor: streakInfo.color }">
        <div class="streak-days">
          <span class="days">{{ streakInfo.streak_days }}</span>
          <span class="label">days</span>
        </div>
        <div class="streak-level-badge" :style="{ background: streakInfo.color }">
          {{ streakInfo.streak_level }}
        </div>
      </div>

      <div class="streak-details">
        <div class="detail-row">
          <span class="label">Multiplier</span>
          <span class="value">{{ streakInfo.multiplier }}x</span>
        </div>
        <div class="detail-row">
          <span class="label">Acceleration Bonus</span>
          <span class="value">+{{ streakInfo.acceleration_bonus }} days</span>
        </div>
        <div v-if="streakInfo.next_level" class="detail-row next-level">
          <span class="label">Next Level</span>
          <span class="value">{{ streakInfo.next_level }}</span>
        </div>
        <div v-if="streakInfo.days_to_next_level" class="detail-row">
          <span class="label">Days to Next</span>
          <span class="value">{{ streakInfo.days_to_next_level }} days</span>
        </div>
      </div>

      <div class="streak-progress">
        <div class="progress-label">
          <span>Current Level</span>
          <span v-if="streakInfo.next_level">Next: {{ streakInfo.next_level }}</span>
        </div>
        <div class="progress-bar">
          <div
            class="progress-fill"
            :style="{ 
              background: streakInfo.color,
              width: calculateProgress() + '%' 
            }"
          ></div>
        </div>
      </div>

      <button @click="processStreak" class="process-btn" :disabled="processing">
        {{ processing ? 'Processing...' : 'Process Streak' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { catfloatApi, type StreakInfo } from '@/services/catfloatApi';

const streakInfo = ref<StreakInfo>({
  streak_days: 0,
  streak_level: 'Bronze',
  multiplier: 1.0,
  color: '#cd7f32',
  is_premium: false,
  acceleration_bonus: 0,
  next_level: null,
  days_to_next_level: null,
});

const loading = ref(true);
const error = ref<string | null>(null);
const processing = ref(false);

const loadStreak = async () => {
  loading.value = true;
  error.value = null;
  
  try {
    streakInfo.value = await catfloatApi.getStreakInfo();
  } catch (err: any) {
    error.value = err.message || 'Failed to load streak';
  } finally {
    loading.value = false;
  }
};

const refreshStreak = async () => {
  await loadStreak();
};

const processStreak = async () => {
  processing.value = true;
  
  try {
    await catfloatApi.processStreak();
    await loadStreak();
  } catch (err: any) {
    error.value = err.message || 'Failed to process streak';
  } finally {
    processing.value = false;
  }
};

const calculateProgress = (): number => {
  if (!streakInfo.value.days_to_next_level) {
    return 100;
  }
  
  const levelDays = streakInfo.value.streak_days;
  const nextLevelDays = streakInfo.value.days_to_next_level + levelDays;
  
  return (levelDays / nextLevelDays) * 100;
};

onMounted(() => {
  loadStreak();
});
</script>

<style scoped>
.streak-display {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.streak-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.streak-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0;
}

.refresh-btn {
  background: none;
  border: none;
  font-size: 16px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background 0.2s;
}

.refresh-btn:hover:not(:disabled) {
  background: #f3f4f6;
}

.refresh-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.loading, .error {
  text-align: center;
  padding: 32px;
  color: #666;
  font-size: 14px;
}

.error {
  color: #ef4444;
}

.streak-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px;
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  border-radius: 8px;
  border: 2px solid #cd7f32;
  margin-bottom: 20px;
}

.streak-days {
  text-align: center;
}

.streak-days .days {
  font-size: 48px;
  font-weight: 700;
  color: #1a1a1a;
  line-height: 1;
}

.streak-days .label {
  font-size: 12px;
  color: #666;
  text-transform: uppercase;
}

.streak-level-badge {
  padding: 8px 16px;
  border-radius: 20px;
  color: white;
  font-weight: 600;
  font-size: 14px;
  text-transform: uppercase;
}

.streak-details {
  display: grid;
  gap: 12px;
  margin-bottom: 20px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #f3f4f6;
}

.detail-row.next-level {
  border-bottom: none;
  font-weight: 600;
  color: #6366f1;
}

.detail-row .label {
  color: #666;
  font-size: 14px;
}

.detail-row .value {
  font-weight: 600;
  color: #1a1a1a;
  font-size: 14px;
}

.streak-progress {
  margin-bottom: 20px;
}

.progress-label {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #666;
  margin-bottom: 8px;
}

.progress-bar {
  height: 8px;
  background: #f3f4f6;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  transition: width 0.3s ease, background 0.3s ease;
}

.process-btn {
  width: 100%;
  padding: 12px;
  border-radius: 8px;
  border: none;
  background: #6366f1;
  color: white;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.process-btn:hover:not(:disabled) {
  background: #4f46e5;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.process-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
