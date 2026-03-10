<template>
<div class="p-6 bg-slate-900 border border-white/10 rounded-3xl backdrop-blur-3xl">
  <div class="flex flex-col gap-6 items-center text-center">
    <div class="w-16 h-16 bg-blue-600/20 rounded-full flex items-center justify-center animate-pulse">
      <span class="text-3xl">📳</span>
    </div>
    
    <div class="space-y-2">
      <h3 class="text-xl font-bold text-white">Умные уведомления</h3>
      <p class="text-slate-400 text-sm leading-relaxed mb-4">
        Узнавайте о новых заказах и выплатах в холдинге моментально, даже когда приложение закрыто.
      </p>
    </div>

    <div v-if="!isSubscribed" class="w-full space-y-4">
      <button @click="subscribe" :disabled="loading"
        class="w-full py-4 bg-white text-slate-900 hover:bg-slate-200 rounded-2xl font-bold transition-all transform active:scale-95 shadow-xl">
        {{ loading ? 'Настройка...' : 'Разрешить уведомления' }}
      </button>
      <p v-if="error" class="text-rose-500 text-xs text-center font-medium">{{ error }}</p>
    </div>

    <div v-else class="w-full space-y-4">
      <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl flex items-center justify-center gap-3">
        <span class="text-emerald-500">🛡️</span>
        <span class="text-emerald-400 text-sm font-semibold italic uppercase">Активно</span>
      </div>
      <button @click="unsubscribe" class="mt-4 text-slate-500 text-xs hover:text-white transition-all underline decoration-slate-700 underline-offset-4">
        Отключить (НЕ РЕКОМЕНДУЕТСЯ)
      </button>
    </div>
  </div>
</div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const isSubscribed = ref(false);
const loading = ref(false);
const error = ref(null);

const subscribe = async () => {
  loading.value = true;
  error.value = null;
  
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    error.value = 'Ваш браузер не поддерживает Push 2026';
    loading.value = false;
    return;
  }

  try {
    const sw = await navigator.serviceWorker.ready;
    const { data } = await axios.get('/api/v1/push-key');
    
    const subscription = await sw.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: data.key
    });

    await axios.post('/api/v1/push-subscribe', subscription);
    isSubscribed.value = true;
  } catch (err) {
    console.error('Push Error:', err);
    error.value = 'Ошибка при активации Push-канала';
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  const sw = await navigator.serviceWorker.ready;
  const sub = await sw.pushManager.getSubscription();
  isSubscribed.value = !!sub;
});

const unsubscribe = async () => {
  const sw = await navigator.serviceWorker.ready;
  const sub = await sw.pushManager.getSubscription();
  if (sub) {
    await sub.unsubscribe();
    await axios.post('/api/v1/push-unsubscribe', { endpoint: sub.endpoint });
    isSubscribed.value = false;
  }
};
</script>
