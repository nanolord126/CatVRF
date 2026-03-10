<template>
<div class="p-6 bg-slate-900 text-white rounded-3xl border border-white/10 shadow-2xl backdrop-blur-xl">
  <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
    <span class="p-2 bg-blue-500 rounded-lg">🛡️</span> Безопасность 2026
  </h2>
  
  <div v-if="!enabled" class="space-y-4">
    <p class="text-slate-400 text-sm">Активируйте двухфакторную аутентификацию для защиты вашего аккаунта в холдинге.</p>
    <button @click="enable2FA" :disabled="loading" 
      class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 rounded-2xl font-semibold transition-all shadow-lg shadow-blue-500/20 active:scale-95">
      {{ loading ? 'Подготовка...' : 'Активировать 2FA' }}
    </button>
  </div>

  <div v-else class="space-y-6">
    <div v-if="qrCode" class="flex flex-col items-center bg-white p-6 rounded-2xl mx-auto w-fit">
      <div v-html="qrCode"></div>
      <p class="mt-4 text-slate-900 font-mono text-xs">{{ recoveryCodes[0] }}</p>
    </div>
    
    <div v-if="confirmed" class="p-4 bg-emerald-500/10 border border-emerald-500/50 rounded-2xl flex items-center gap-3">
      <span class="text-emerald-400">✅</span>
      <span class="text-emerald-500 text-sm font-medium">Защищено на 100%</span>
    </div>

    <button @click="disable2FA" class="w-full py-3 bg-red-500/10 text-red-500 hover:bg-red-500/20 rounded-2xl text-sm font-medium transition-all">
      Отключить защиту
    </button>
  </div>
</div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps(['user']);
const loading = ref(false);
const enabled = ref(false);
const qrCode = ref(null);
const recoveryCodes = ref([]);
const confirmed = ref(false);

const checkStatus = async () => {
  const { data } = await axios.get('/user/two-factor-authentication-status');
  enabled.value = data.enabled;
};

const enable2FA = async () => {
  loading.value = true;
  await axios.post('/user/two-factor-authentication');
  await showQR();
  enabled.value = true;
  loading.value = false;
};

const showQR = async () => {
  const [qr, codes] = await Promise.all([
    axios.get('/user/two-factor-qr-code'),
    axios.get('/user/two-factor-recovery-codes')
  ]);
  qrCode.value = qr.data.svg;
  recoveryCodes.value = codes.data;
};

const disable2FA = async () => {
  await axios.delete('/user/two-factor-authentication');
  enabled.value = false;
  qrCode.value = null;
};

onMounted(checkStatus);
</script>
