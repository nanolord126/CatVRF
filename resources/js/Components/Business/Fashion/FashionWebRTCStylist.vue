<template>
  <div class="fashion-webrtc-stylist bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-4 text-white">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
              <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
            </svg>
          </div>
          <div>
            <h3 class="font-bold">Personal Stylist</h3>
            <p class="text-sm text-purple-200">{{ stylistName }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span v-if="isRecording" class="bg-red-500 px-2 py-1 rounded text-xs font-bold animate-pulse">REC</span>
          <span class="bg-white/20 px-3 py-1 rounded-full text-sm">{{ sessionDuration }}</span>
        </div>
      </div>
    </div>

    <div class="relative aspect-video bg-gray-900">
      <video
        ref="localVideo"
        autoplay
        muted
        playsinline
        class="w-full h-full object-cover"
      ></video>
      <video
        ref="remoteVideo"
        autoplay
        playsinline
        class="absolute top-4 right-4 w-32 h-24 object-cover rounded-lg border-2 border-white shadow-lg"
      ></video>

      <div v-if="!isConnected" class="absolute inset-0 flex items-center justify-center bg-black/50">
        <div class="text-center text-white">
          <div class="mb-4">
            <svg class="w-16 h-16 mx-auto mb-2 opacity-50" fill="currentColor" viewBox="0 0 20 20">
              <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
            </svg>
            <p class="text-lg font-semibold">Connecting to stylist...</p>
          </div>
          <div class="animate-spin w-8 h-8 border-2 border-white border-t-transparent rounded-full mx-auto"></div>
        </div>
      </div>

      <div v-if="showControls" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex items-center gap-3">
        <button
          @click="toggleAudio"
          :class="isAudioEnabled ? 'bg-white' : 'bg-red-500'"
          class="w-12 h-12 rounded-full flex items-center justify-center shadow-lg transition-colors"
        >
          <svg v-if="isAudioEnabled" class="w-6 h-6 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"/>
          </svg>
          <svg v-else class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd"/>
          </svg>
        </button>
        <button
          @click="toggleVideo"
          :class="isVideoEnabled ? 'bg-white' : 'bg-red-500'"
          class="w-12 h-12 rounded-full flex items-center justify-center shadow-lg transition-colors"
        >
          <svg v-if="isVideoEnabled" class="w-6 h-6 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
          </svg>
          <svg v-else class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
            <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
          </svg>
        </button>
        <button
          @click="endCall"
          class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center shadow-lg hover:bg-red-600 transition-colors"
        >
          <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
          </svg>
        </button>
      </div>
    </div>

    <div class="p-4 border-t">
      <div class="flex items-center gap-3">
        <div class="flex-1 relative">
          <input
            v-model="chatMessage"
            @keyup.enter="sendMessage"
            type="text"
            placeholder="Type a message..."
            class="w-full px-4 py-2 pr-10 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
          >
          <button
            @click="sendMessage"
            class="absolute right-2 top-1/2 transform -translate-y-1/2 text-purple-600 hover:text-purple-800"
          >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
            </svg>
          </button>
        </div>
        <button
          @click="shareProduct"
          class="px-4 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors"
        >
          Share Product
        </button>
      </div>

      <div v-if="messages.length > 0" class="mt-4 max-h-32 overflow-y-auto space-y-2">
        <div
          v-for="(msg, index) in messages"
          :key="index"
          :class="msg.from === 'user' ? 'bg-purple-100 ml-auto' : 'bg-gray-100'"
          class="max-w-xs px-3 py-2 rounded-lg"
        >
          <p class="text-sm">{{ msg.text }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
  sessionId: string;
  stylistId?: number;
}>();

const localVideo = ref<HTMLVideoElement>();
const remoteVideo = ref<HTMLVideoElement>();
const isConnected = ref(false);
const isAudioEnabled = ref(true);
const isVideoEnabled = ref(true);
const isRecording = ref(false);
const showControls = ref(true);
const stylistName = ref('Expert Stylist');
const sessionDuration = ref('00:00');
const chatMessage = ref('');
const messages = ref<Array<{ from: 'user' | 'stylist'; text: string }>>([]);

let durationTimer: number | null = null;
let secondsElapsed = 0;

const toggleAudio = () => {
  isAudioEnabled.value = !isAudioEnabled.value;
};

const toggleVideo = () => {
  isVideoEnabled.value = !isVideoEnabled.value;
};

const endCall = () => {
  // Implement WebRTC cleanup
  isConnected.value = false;
  if (durationTimer) {
    clearInterval(durationTimer);
  }
};

const sendMessage = () => {
  if (chatMessage.value.trim()) {
    messages.value.push({
      from: 'user',
      text: chatMessage.value
    });
    chatMessage.value = '';
  }
};

const shareProduct = () => {
  // Implement product sharing
  console.log('Share product clicked');
};

const updateDuration = () => {
  secondsElapsed++;
  const hours = Math.floor(secondsElapsed / 3600);
  const minutes = Math.floor((secondsElapsed % 3600) / 60);
  const seconds = secondsElapsed % 60;
  sessionDuration.value = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
};

onMounted(() => {
  // Initialize WebRTC connection
  setTimeout(() => {
    isConnected.value = true;
    durationTimer = window.setInterval(updateDuration, 1000);
  }, 2000);
});

onUnmounted(() => {
  if (durationTimer) {
    clearInterval(durationTimer);
  }
});
</script>
