<template>
  <div class="beauty-video-call">
    <h3>Видеозвонок с мастером</h3>

    <div class="input-section">
      <div class="input-group">
        <label>ID пользователя:</label>
        <input type="number" v-model="userId" min="1" />
      </div>

      <div class="input-group">
        <label>ID мастера:</label>
        <input type="number" v-model="masterId" min="1" />
      </div>

      <div class="input-group">
        <label>Назначено на:</label>
        <input type="datetime-local" v-model="scheduledFor" />
      </div>

      <div class="input-group">
        <label>Длительность (мин):</label>
        <input type="number" v-model="durationMinutes" min="1" max="30" />
      </div>
    </div>

    <button @click="initiateCall" :disabled="loading" class="btn-initiate">
      <Video class="icon" />
      {{ loading ? 'Создание...' : 'Начать звонок' }}
    </button>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>

    <div v-if="callData" class="call-section">
      <div class="call-info">
        <h4>Видеозвонок создан</h4>
        <div class="info-item">
          <span class="label">ID звонка:</span>
          <span class="value">{{ callData.call_id }}</span>
        </div>

        <div class="info-item">
          <span class="label">Комната:</span>
          <span class="value">{{ callData.room_name }}</span>
        </div>

        <div class="info-item">
          <span class="label">Мастер:</span>
          <span class="value">{{ callData.master_name }}</span>
        </div>

        <div class="info-item">
          <span class="label">Длительность:</span>
          <span class="value">{{ Math.floor(callData.duration_seconds / 60) }} мин</span>
        </div>

        <div class="info-item">
          <span class="label">Истекает:</span>
          <span class="value">{{ new Date(callData.expires_at).toLocaleString() }}</span>
        </div>

        <div class="token-section">
          <span class="label">Токен:</span>
          <div class="token-box">
            <code>{{ callData.token }}</code>
            <button @click="copyToken" class="btn-copy">Копировать</button>
          </div>
        </div>
      </div>

      <div class="call-controls">
        <button @click="joinCall" class="btn-join">
          <Phone class="icon" />
          Присоединиться
        </button>

        <div class="end-call-section">
          <select v-model="endReason" class="reason-select">
            <option value="user_ended">Завершен пользователем</option>
            <option value="master_ended">Завершен мастером</option>
            <option value="connection_lost">Потеря связи</option>
            <option value="timeout">Таймаут</option>
          </select>

          <button @click="endCall" class="btn-end">
            <PhoneOff class="icon" />
            Завершить звонок
          </button>
        </div>
      </div>
    </div>

    <div v-if="callActive" class="active-call">
      <div class="video-container">
        <div class="video-placeholder local">
          <Camera class="icon" />
          <span>Ваше видео</span>
        </div>
        <div class="video-placeholder remote">
          <User class="icon" />
          <span>{{ callData?.master_name }}</span>
        </div>
      </div>

      <div class="call-controls-active">
        <button @click="toggleMute" :class="{ active: isMuted }" class="control-btn">
          <MicOff v-if="isMuted" class="icon" />
          <Mic v-else class="icon" />
        </button>

        <button @click="toggleVideo" :class="{ active: isVideoOff }" class="control-btn">
          <VideoOff v-if="isVideoOff" class="icon" />
          <Video v-else class="icon" />
        </button>

        <button @click="endCall" class="control-btn end">
          <PhoneOff class="icon" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Video, Phone, PhoneOff, Camera, User, Mic, MicOff, VideoOff } from 'lucide-vue-next';

const userId = ref<number>(1);
const masterId = ref<number>(1);
const scheduledFor = ref<string>('');
const durationMinutes = ref<number>(5);
const loading = ref(false);
const error = ref<string | null>(null);
const callData = ref<any>(null);
const callActive = ref(false);
const isMuted = ref(false);
const isVideoOff = ref(false);
const endReason = ref('user_ended');

const initiateCall = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await fetch('/api/beauty/video-calls/initiate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': crypto.randomUUID(),
        'X-Tenant-ID': '1',
      },
      body: JSON.stringify({
        user_id: userId.value,
        master_id: masterId.value,
        scheduled_for: scheduledFor.value || null,
        duration_minutes: durationMinutes.value,
      }),
    });

    const data = await response.json();
    
    if (data.success) {
      callData.value = data.data;
    } else {
      error.value = 'Ошибка при создании звонка';
    }
  } catch (err) {
    error.value = 'Произошла ошибка. Попробуйте позже.';
  } finally {
    loading.value = false;
  }
};

const joinCall = () => {
  callActive.value = true;
};

const endCall = async () => {
  if (!callData.value) return;

  try {
    const response = await fetch('/api/beauty/video-calls/end', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Tenant-ID': '1',
      },
      body: JSON.stringify({
        call_id: callData.value.call_id,
        duration_seconds: 300,
        reason: endReason.value,
      }),
    });

    const data = await response.json();
    
    if (data.success) {
      callActive.value = false;
      callData.value = null;
    }
  } catch (err) {
    error.value = 'Ошибка при завершении звонка';
  }
};

const copyToken = () => {
  if (callData.value) {
    navigator.clipboard.writeText(callData.value.token);
  }
};

const toggleMute = () => {
  isMuted.value = !isMuted.value;
};

const toggleVideo = () => {
  isVideoOff.value = !isVideoOff.value;
};
</script>

<style scoped>
.beauty-video-call {
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
}

.input-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.input-group label {
  font-weight: 600;
  font-size: 14px;
  color: #374151;
}

.input-group input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.btn-initiate {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px 28px;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
  transition: transform 0.2s;
  width: 100%;
}

.btn-initiate:hover:not(:disabled) {
  transform: scale(1.02);
}

.btn-initiate:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-message {
  margin-top: 16px;
  padding: 12px;
  background: #fee2e2;
  color: #dc2626;
  border-radius: 6px;
  text-align: center;
}

.call-section {
  margin-top: 24px;
  padding: 20px;
  background: #f9fafb;
  border-radius: 8px;
}

.call-info h4 {
  margin: 0 0 16px 0;
  color: #111827;
}

.info-item {
  display: flex;
  justify-content: space-between;
  padding: 12px;
  background: white;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  margin-bottom: 8px;
}

.info-item .label {
  font-weight: 600;
  color: #374151;
}

.info-item .value {
  font-weight: 700;
  color: #111827;
}

.token-section {
  margin-top: 16px;
}

.token-section .label {
  display: block;
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
}

.token-box {
  display: flex;
  gap: 8px;
}

.token-box code {
  flex: 1;
  padding: 12px;
  background: #1f2937;
  color: #10b981;
  border-radius: 6px;
  font-family: monospace;
  font-size: 12px;
  word-break: break-all;
}

.btn-copy {
  padding: 8px 16px;
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
}

.call-controls {
  display: flex;
  gap: 12px;
  margin-top: 20px;
  flex-wrap: wrap;
}

.btn-join {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}

.end-call-section {
  display: flex;
  gap: 8px;
  align-items: center;
}

.reason-select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.btn-end {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}

.active-call {
  margin-top: 24px;
}

.video-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}

.video-placeholder {
  aspect-ratio: 16 / 9;
  background: #1f2937;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  gap: 8px;
}

.video-placeholder.remote {
  background: #374151;
}

.call-controls-active {
  display: flex;
  justify-content: center;
  gap: 16px;
}

.control-btn {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  background: #6366f1;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s;
}

.control-btn:hover {
  transform: scale(1.1);
}

.control-btn.active {
  background: #ef4444;
}

.control-btn.end {
  background: #ef4444;
}

.icon {
  width: 24px;
  height: 24px;
}
</style>
