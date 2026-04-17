<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

interface SearchResult {
  id: number;
  name: string;
  type: 'part' | 'service' | 'vehicle';
  price?: number;
  rating?: number;
}

const isListening = ref(false);
const transcript = ref('');
const searchResults = ref<SearchResult[]>([]);
const isProcessing = ref(false);
const recognition = ref<SpeechRecognition | null>(null);

const startListening = () => {
  if (!recognition.value) {
    alert('Голосовой поиск не поддерживается вашим браузером');
    return;
  }

  isListening.value = true;
  transcript.value = '';
  recognition.value.start();
};

const stopListening = () => {
  if (recognition.value) {
    recognition.value.stop();
  }
  isListening.value = false;
};

const processVoiceInput = async (text: string) => {
  isProcessing.value = true;
  transcript.value = text;

  try {
    const response = await fetch('/api/v1/auto/voice-search', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ query: text }),
    });

    const data = await response.json();
    searchResults.value = data.results || [];
  } catch (error) {
    console.error('Error processing voice search:', error);
  } finally {
    isProcessing.value = false;
  }
};

const selectResult = (result: SearchResult) => {
  emit('select', result);
};

const emit = defineEmits<{
  select: [result: SearchResult];
  close: [];
}>();

onMounted(() => {
  if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
    const SpeechRecognition = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition;
    recognition.value = new SpeechRecognition();
    recognition.value.lang = 'ru-RU';
    recognition.value.continuous = false;
    recognition.value.interimResults = true;

    recognition.value.onresult = (event: SpeechRecognitionEvent) => {
      let interimTranscript = '';
      let finalTranscript = '';

      for (let i = event.resultIndex; i < event.results.length; i++) {
        const transcriptItem = event.results[i][0].transcript;
        if (event.results[i].isFinal) {
          finalTranscript += transcriptItem;
        } else {
          interimTranscript += transcriptItem;
        }
      }

      if (finalTranscript) {
        transcript.value = finalTranscript;
        processVoiceInput(finalTranscript);
      } else {
        transcript.value = interimTranscript;
      }
    };

    recognition.value.onerror = (event: SpeechRecognitionErrorEvent) => {
      console.error('Speech recognition error:', event.error);
      isListening.value = false;
    };

    recognition.value.onend = () => {
      isListening.value = false;
    };
  }
});

onUnmounted(() => {
  if (recognition.value) {
    recognition.value.abort();
  }
});

const formatPrice = (price?: number) => {
  if (!price) return '';
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(price);
};
</script>

<template>
  <div class="voice-search p-6 bg-white rounded-xl shadow-lg max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Голосовой поиск</h2>
      <button 
        @click="$emit('close')"
        class="p-2 hover:bg-gray-100 rounded-full transition-colors"
      >
        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div class="space-y-6">
      <div class="flex flex-col items-center justify-center p-8 bg-gray-50 rounded-xl">
        <button
          @click="isListening ? stopListening() : startListening()"
          :disabled="isProcessing"
          class="w-24 h-24 rounded-full flex items-center justify-center transition-all duration-300"
          :class="{
            'bg-red-500 hover:bg-red-600 animate-pulse': isListening,
            'bg-blue-600 hover:bg-blue-700': !isListening,
            'opacity-50 cursor-not-allowed': isProcessing
          }"
        >
          <svg v-if="!isListening" class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
          </svg>
          <svg v-else class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
          </svg>
        </button>
        
        <p class="mt-4 text-gray-600 text-center">
          {{ isListening ? 'Слушаю...' : 'Нажмите и говорите' }}
        </p>
        
        <p v-if="transcript" class="mt-2 text-lg font-medium text-gray-900 text-center">
          "{{ transcript }}"
        </p>
      </div>

      <div v-if="isProcessing" class="flex items-center justify-center p-4">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mr-3"></div>
        <span class="text-gray-600">Обработка запроса...</span>
      </div>

      <div v-if="searchResults.length > 0" class="space-y-3">
        <h3 class="font-semibold text-gray-900">Результаты поиска</h3>
        
        <div
          v-for="result in searchResults"
          :key="result.id"
          @click="selectResult(result)"
          class="p-4 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
        >
          <div class="flex justify-between items-start">
            <div>
              <p class="font-medium text-gray-900">{{ result.name }}</p>
              <div class="flex gap-2 mt-1">
                <span class="px-2 py-0.5 text-xs rounded-full"
                      :class="{
                        'bg-blue-100 text-blue-800': result.type === 'part',
                        'bg-green-100 text-green-800': result.type === 'service',
                        'bg-purple-100 text-purple-800': result.type === 'vehicle'
                      }">
                  {{ result.type === 'part' ? 'Запчасть' : result.type === 'service' ? 'Услуга' : 'Автомобиль' }}
                </span>
                <span v-if="result.rating" class="flex items-center gap-1 text-sm text-yellow-600">
                  ⭐ {{ result.rating }}
                </span>
              </div>
            </div>
            <p v-if="result.price" class="font-semibold text-blue-600">
              {{ formatPrice(result.price) }}
            </p>
          </div>
        </div>
      </div>

      <div v-if="transcript && searchResults.length === 0 && !isProcessing" class="p-4 bg-yellow-50 text-yellow-800 rounded-lg">
        <p>По запросу "{{ transcript }}" ничего не найдено. Попробуйте переформулировать.</p>
      </div>

      <div class="text-center text-sm text-gray-500">
        <p>Поддерживаемые команды:</p>
        <p class="mt-1">"Найти запчасть тормозные колодки", "Услуги по замене масла", "Автомобили Toyota"</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.voice-search {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>
