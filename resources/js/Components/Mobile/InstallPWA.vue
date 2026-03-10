<template>
  <div v-if="showInstall" class="fixed bottom-24 inset-x-4 z-[100] animate-in fade-in slide-in-from-bottom-10 duration-700">
    <div class="bg-white/10 backdrop-blur-3xl border border-white/20 p-6 rounded-[2.5rem] shadow-2xl flex items-center justify-between gap-4 overflow-hidden relative group">
      <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-600/20 blur-3xl rounded-full group-hover:bg-blue-600/40 transition-all duration-1000"></div>
      
      <div class="flex items-center gap-4 relative z-10">
        <div class="w-12 h-12 bg-gradient-to-br from-blue-600 to-indigo-900 rounded-2xl flex items-center justify-center text-white shadow-xl">
          <svg class="w-6 h-6" fill="white" viewBox="0 0 24 24"><path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71L12 2z"/></svg>
        </div>
        <div>
          <h4 class="text-white font-black text-sm uppercase tracking-widest">Install CatVRF</h4>
          <p class="text-gray-400 text-[10px] font-bold opacity-80">Better UI & Offline mode</p>
        </div>
      </div>

      <button @click="install" class="px-6 py-3 bg-white text-black font-black text-xs uppercase rounded-2xl hover:scale-105 active:scale-90 transition-all shadow-white/20 shadow-lg relative z-10">
        Install
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const showInstall = ref(false);
let deferredPrompt = null;

onMounted(() => {
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    showInstall.value = true;
  });

  window.addEventListener('appinstalled', () => {
    showInstall.value = false;
    deferredPrompt = null;
  });
});

const install = async () => {
  if (!deferredPrompt) return;
  deferredPrompt.prompt();
  const { outcome } = await deferredPrompt.userChoice;
  if (outcome === 'accepted') showInstall.value = false;
  deferredPrompt = null;
};
</script>
