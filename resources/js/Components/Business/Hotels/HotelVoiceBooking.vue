<template>
  <div class="hotel-voice-booking">
    <div class="voice-interface">
      <div class="chat-container">
        <div class="chat-header">
          <div class="header-content">
            <div class="bot-avatar">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
              </svg>
            </div>
            <div class="header-text">
              <h3>Голосовой помощник</h3>
              <p class="status" :class="{ online: isOnline, processing: isProcessing }">
                {{ statusText }}
              </p>
            </div>
          </div>
          <button @click="toggleVoice" :class="['voice-toggle', { active: isListening }]" title="Голосовой ввод">
            <svg v-if="!isListening" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
              <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
              <line x1="12" y1="19" x2="12" y2="23"></line>
              <line x1="8" y1="23" x2="16" y2="23"></line>
            </svg>
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <rect x="6" y="4" width="4" height="16"></rect>
              <rect x="14" y="4" width="4" height="16"></rect>
            </svg>
          </button>
        </div>

        <div ref="chatMessages" class="chat-messages">
          <div
            v-for="(message, index) in messages"
            :key="index"
            :class="['message', message.role]"
          >
            <div v-if="message.role === 'bot'" class="bot-avatar-small">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
              </svg>
            </div>
            <div class="message-content">
              <div class="message-text">{{ message.text }}</div>
              <div v-if="message.bookingPreview" class="booking-preview">
                <div class="preview-header">Предварительное бронирование</div>
                <div class="preview-item">
                  <span class="label">Отель:</span>
                  <span class="value">{{ message.bookingPreview.hotelName }}</span>
                </div>
                <div class="preview-item">
                  <span class="label">Номер:</span>
                  <span class="value">{{ message.bookingPreview.roomName }}</span>
                </div>
                <div class="preview-item">
                  <span class="label">Даты:</span>
                  <span class="value">{{ message.bookingPreview.dates }}</span>
                </div>
                <div class="preview-item">
                  <span class="label">Гостей:</span>
                  <span class="value">{{ message.bookingPreview.guests }}</span>
                </div>
                <div class="preview-item total">
                  <span class="label">Итого:</span>
                  <span class="value">{{ formatPrice(message.bookingPreview.price) }}</span>
                </div>
                <div class="preview-actions">
                  <button @click="confirmBooking(message.bookingPreview)" class="confirm-btn">
                    Подтвердить
                  </button>
                  <button @click="cancelPreview" class="cancel-btn">
                    Изменить
                  </button>
                </div>
              </div>
              <div v-if="message.quickActions" class="quick-actions">
                <button
                  v-for="(action, actionIndex) in message.quickActions"
                  :key="actionIndex"
                  @click="handleQuickAction(action)"
                  class="quick-action-btn"
                >
                  {{ action.label }}
                </button>
              </div>
              <div class="message-time">{{ formatTime(message.timestamp) }}</div>
            </div>
          </div>
          
          <div v-if="isProcessing" class="message bot">
            <div class="bot-avatar-small">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
              </svg>
            </div>
            <div class="message-content">
              <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
              </div>
            </div>
          </div>
        </div>

        <div class="chat-input">
          <div class="input-wrapper">
            <input
              v-model="inputText"
              @keyup.enter="sendMessage"
              type="text"
              placeholder="Напишите или скажите..."
              class="text-input"
              :disabled="isProcessing"
            />
            <button
              @click="sendMessage"
              :disabled="!inputText.trim() || isProcessing"
              class="send-button"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
              </svg>
            </button>
          </div>
          <div v-if="isListening" class="voice-indicator">
            <div class="wave">
              <span></span>
              <span></span>
              <span></span>
              <span></span>
              <span></span>
            </div>
            <p>Слушаю...</p>
          </div>
        </div>
      </div>

      <div class="voice-commands">
        <h4>Голосовые команды</h4>
        <div class="commands-list">
          <div class="command-item" @click="executeCommand('search')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <div class="command-text">
              <span class="command-label">Найти отель</span>
              <span class="command-example">"Найди отель в Москве на выходные"</span>
            </div>
          </div>
          <div class="command-item" @click="executeCommand('book')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
            </svg>
            <div class="command-text">
              <span class="command-label">Забронировать</span>
              <span class="command-example">"Забронируй номер на двоих"</span>
            </div>
          </div>
          <div class="command-item" @click="executeCommand('info')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <div class="command-text">
              <span class="command-label">Информация</span>
              <span class="command-example">"Расскажи об отеле"</span>
            </div>
          </div>
          <div class="command-item" @click="executeCommand('cancel')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
            <div class="command-text">
              <span class="command-label">Отмена</span>
              <span class="command-example">"Отмени бронирование"</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';

interface Message {
  role: 'user' | 'bot';
  text: string;
  timestamp: Date;
  bookingPreview?: BookingPreview;
  quickActions?: QuickAction[];
}

interface BookingPreview {
  hotelId: number;
  hotelName: string;
  roomId: number;
  roomName: string;
  checkInDate: string;
  checkOutDate: string;
  guests: number;
  price: number;
}

interface QuickAction {
  label: string;
  action: string;
  params?: Record<string, any>;
}

const props = defineProps<{
  hotelId?: number;
}>();

const route = useRoute();
const router = useRouter();

const isOnline = ref(true);
const isListening = ref(false);
const isProcessing = ref(false);
const inputText = ref('');
const messages = ref<Message[]>([
  {
    role: 'bot',
    text: 'Здравствуйте! Я голосовой помощник CatVRF Hotels. Чем могу помочь? Вы можете написать или сказать мне, что ищете.',
    timestamp: new Date(),
    quickActions: [
      { label: 'Найти отель', action: 'search' },
      { label: 'Мои бронирования', action: 'my-bookings' },
      { label: 'Популярные направления', action: 'popular' },
    ],
  },
]);

const chatMessages = ref<HTMLDivElement | null>(null);
let recognition: any = null;
let synthesis: SpeechSynthesis | null = null;

const statusText = computed(() => {
  if (isListening.value) return 'Слушаю...';
  if (isProcessing.value) return 'Обрабатываю...';
  return isOnline.value ? 'Онлайн' : 'Офлайн';
});

const initializeSpeechRecognition = () => {
  if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
    const SpeechRecognition = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition;
    recognition = new SpeechRecognition();
    recognition.continuous = false;
    recognition.interimResults = false;
    recognition.lang = 'ru-RU';

    recognition.onstart = () => {
      isListening.value = true;
    };

    recognition.onresult = (event: any) => {
      const transcript = event.results[0][0].transcript;
      inputText.value = transcript;
      sendMessage();
    };

    recognition.onerror = (event: any) => {
      console.error('Speech recognition error:', event.error);
      isListening.value = false;
      addBotMessage('Извините, не удалось распознать речь. Попробуйте написать сообщение.');
    };

    recognition.onend = () => {
      isListening.value = false;
    };
  }
};

const initializeSpeechSynthesis = () => {
  synthesis = window.speechSynthesis;
};

const toggleVoice = () => {
  if (isListening.value) {
    recognition?.stop();
  } else {
    recognition?.start();
  }
};

const sendMessage = async () => {
  const text = inputText.value.trim();
  if (!text || isProcessing.value) return;

  addUserMessage(text);
  inputText.value = '';
  isProcessing.value = true;

  await processMessage(text);
  
  isProcessing.value = false;
  scrollToBottom();
};

const processMessage = async (text: string) => {
  try {
    const response = await fetch('/api/v1/hotels/voice-booking/process', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        message: text,
        hotelId: props.hotelId,
        context: getConversationContext(),
      }),
    });

    if (!response.ok) {
      throw new Error('Failed to process message');
    }

    const data = await response.json();
    
    addBotMessage(data.response, data.bookingPreview, data.quickActions);
    
    if (data.speak) {
      speak(data.response);
    }
  } catch (error) {
    console.error('Error processing message:', error);
    addBotMessage('Извините, произошла ошибка. Попробуйте позже.');
  }
};

const addUserMessage = (text: string) => {
  messages.value.push({
    role: 'user',
    text,
    timestamp: new Date(),
  });
  scrollToBottom();
};

const addBotMessage = (
  text: string,
  bookingPreview?: BookingPreview,
  quickActions?: QuickAction[]
) => {
  messages.value.push({
    role: 'bot',
    text,
    timestamp: new Date(),
    bookingPreview,
    quickActions,
  });
  scrollToBottom();
};

const speak = (text: string) => {
  if (!synthesis) return;

  synthesis.cancel();
  const utterance = new SpeechSynthesisUtterance(text);
  utterance.lang = 'ru-RU';
  utterance.rate = 1;
  utterance.pitch = 1;
  synthesis.speak(utterance);
};

const handleQuickAction = (action: QuickAction) => {
  addUserMessage(action.label);
  isProcessing.value = true;

  processQuickAction(action).finally(() => {
    isProcessing.value = false;
  });
};

const processQuickAction = async (action: QuickAction) => {
  try {
    const response = await fetch('/api/v1/hotels/voice-booking/action', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        action: action.action,
        params: action.params,
        hotelId: props.hotelId,
      }),
    });

    if (!response.ok) {
      throw new Error('Failed to process action');
    }

    const data = await response.json();
    addBotMessage(data.response, data.bookingPreview, data.quickActions);
    
    if (data.speak) {
      speak(data.response);
    }
  } catch (error) {
    console.error('Error processing action:', error);
    addBotMessage('Извините, произошла ошибка. Попробуйте позже.');
  }
};

const executeCommand = (command: string) => {
  const commands: Record<string, string> = {
    search: 'Найди отель в Москве на следующие выходные',
    book: 'Хочу забронировать номер',
    info: 'Расскажи об этом отеле',
    cancel: 'Отмени моё последнее бронирование',
  };

  inputText.value = commands[command] || '';
  sendMessage();
};

const confirmBooking = (preview: BookingPreview) => {
  router.push({
    name: 'hotel-booking',
    params: {
      hotelId: preview.hotelId,
      roomId: preview.roomId,
    },
    query: {
      checkIn: preview.checkInDate,
      checkOut: preview.checkOutDate,
      guests: preview.guests,
    },
  });
};

const cancelPreview = () => {
  addBotMessage('Хорошо, давайте изменим параметры. Что вы хотите изменить?');
};

const getConversationContext = () => {
  return messages.value.slice(-5).map(m => ({
    role: m.role,
    text: m.text,
  }));
};

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(price);
};

const formatTime = (date: Date): string => {
  return new Intl.DateTimeFormat('ru-RU', {
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
};

const scrollToBottom = () => {
  nextTick(() => {
    if (chatMessages.value) {
      chatMessages.value.scrollTop = chatMessages.value.scrollHeight;
    }
  });
};

onMounted(() => {
  initializeSpeechRecognition();
  initializeSpeechSynthesis();
  scrollToBottom();
});

onUnmounted(() => {
  recognition?.stop();
  synthesis?.cancel();
});
</script>

<style scoped>
.hotel-voice-booking {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.voice-interface {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 20px;
  height: 700px;
}

@media (max-width: 900px) {
  .voice-interface {
    grid-template-columns: 1fr;
    height: auto;
  }
}

.chat-container {
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.chat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.bot-avatar {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bot-avatar svg {
  width: 24px;
  height: 24px;
}

.header-text h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.status {
  margin: 4px 0 0 0;
  font-size: 13px;
  opacity: 0.9;
}

.status.online {
  color: #10b981;
}

.status.processing {
  color: #fbbf24;
}

.voice-toggle {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.2);
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
}

.voice-toggle:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: scale(1.05);
}

.voice-toggle.active {
  background: #ef4444;
  border-color: #ef4444;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
  }
  50% {
    box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
  }
}

.voice-toggle svg {
  width: 20px;
  height: 20px;
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.message {
  display: flex;
  gap: 12px;
  max-width: 80%;
}

.message.user {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.bot-avatar-small {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.bot-avatar-small svg {
  width: 18px;
  height: 18px;
  color: white;
}

.message-content {
  background: #f3f4f6;
  padding: 14px 18px;
  border-radius: 18px;
  position: relative;
}

.message.user .message-content {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.message-text {
  line-height: 1.5;
  font-size: 15px;
}

.booking-preview {
  margin-top: 12px;
  background: white;
  border-radius: 12px;
  padding: 16px;
  border: 1px solid #e5e7eb;
}

.message.user .booking-preview {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

.preview-header {
  font-weight: 600;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #e5e7eb;
}

.message.user .preview-header {
  border-bottom-color: rgba(255, 255, 255, 0.2);
}

.preview-item {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;
  font-size: 14px;
}

.preview-item .label {
  color: #6b7280;
}

.message.user .preview-item .label {
  color: rgba(255, 255, 255, 0.7);
}

.preview-item .value {
  font-weight: 500;
}

.preview-item.total {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid #e5e7eb;
  font-weight: 600;
  font-size: 16px;
}

.message.user .preview-item.total {
  border-top-color: rgba(255, 255, 255, 0.2);
}

.preview-actions {
  display: flex;
  gap: 8px;
  margin-top: 12px;
}

.confirm-btn {
  flex: 1;
  padding: 10px;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.confirm-btn:hover {
  background: #059669;
}

.cancel-btn {
  flex: 1;
  padding: 10px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.cancel-btn:hover {
  background: #dc2626;
}

.quick-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.quick-action-btn {
  padding: 8px 16px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 20px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.message.user .quick-action-btn {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
  color: white;
}

.quick-action-btn:hover {
  background: #f3f4f6;
  transform: translateY(-2px);
}

.message.user .quick-action-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.message-time {
  font-size: 11px;
  color: #9ca3af;
  margin-top: 8px;
}

.message.user .message-time {
  color: rgba(255, 255, 255, 0.7);
}

.typing-indicator {
  display: flex;
  gap: 4px;
  padding: 8px 0;
}

.typing-indicator span {
  width: 8px;
  height: 8px;
  background: #9ca3af;
  border-radius: 50%;
  animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typing {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-5px);
  }
}

.chat-input {
  padding: 16px 20px;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
}

.input-wrapper {
  display: flex;
  gap: 12px;
  align-items: center;
}

.text-input {
  flex: 1;
  padding: 14px 18px;
  border: 2px solid #e5e7eb;
  border-radius: 24px;
  font-size: 15px;
  outline: none;
  transition: border-color 0.2s;
}

.text-input:focus {
  border-color: #667eea;
}

.text-input:disabled {
  background: #f3f4f6;
  cursor: not-allowed;
}

.send-button {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 50%;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.send-button:hover:not(:disabled) {
  transform: scale(1.05);
}

.send-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.send-button svg {
  width: 20px;
  height: 20px;
}

.voice-indicator {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 12px;
  padding: 12px;
  background: #fef3c7;
  border-radius: 12px;
  color: #92400e;
}

.wave {
  display: flex;
  gap: 4px;
  align-items: center;
}

.wave span {
  width: 4px;
  height: 20px;
  background: #f59e0b;
  border-radius: 2px;
  animation: wave 1s infinite;
}

.wave span:nth-child(2) {
  animation-delay: 0.1s;
}

.wave span:nth-child(3) {
  animation-delay: 0.2s;
}

.wave span:nth-child(4) {
  animation-delay: 0.3s;
}

.wave span:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes wave {
  0%, 100% {
    height: 10px;
  }
  50% {
    height: 30px;
  }
}

.voice-commands {
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
  padding: 20px;
}

.voice-commands h4 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.commands-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.command-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.command-item:hover {
  background: #f3f4f6;
  transform: translateX(4px);
}

.command-item svg {
  width: 20px;
  height: 20px;
  color: #667eea;
}

.command-text {
  display: flex;
  flex-direction: column;
}

.command-label {
  font-weight: 500;
  font-size: 14px;
  color: #1f2937;
}

.command-example {
  font-size: 12px;
  color: #6b7280;
  margin-top: 2px;
}
</style>
