<template>
  <div class="p-6 bg-slate-900 text-white rounded-xl shadow-2xl border border-pink-500/20">
    <div class="flex flex-col md:flex-row justify-between items-start mb-6 gap-4">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-pink-400 to-rose-600 bg-clip-text text-transparent">
          Beauty Try-On AI
        </h2>
        <p class="text-xs text-slate-400">На базе Inventory вашего салона</p>
      </div>
      <div class="flex gap-2">
        <button v-for="t in types" :key="t.id" @click="activeType = t.id"
                :class="t.id === activeType ? 'bg-pink-600 border-pink-500' : 'bg-slate-800 border-slate-700'"
                class="px-4 py-2 rounded-lg text-sm border transition-all">
          {{ t.label }}
        </button>
      </div>
    </div>

    <!-- Interface -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <!-- Preview -->
      <div class="preview relative aspect-square bg-slate-800 rounded-2xl overflow-hidden border border-slate-700 group">
        <img v-if="resultImage" :src="resultImage" class="w-full h-full object-cover" />
        <div v-else class="flex flex-col items-center justify-center h-full">
          <i class="fas fa-magic text-5xl mb-4 text-pink-500 animate-pulse"></i>
          <p class="text-sm font-light">Выберите фото или сделайте селфи</p>
        </div>
        <!-- Result Overlay -->
        <div v-if="loading" class="absolute inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center">
          <div class="text-center">
            <div class="w-12 h-12 border-4 border-pink-500 border-t-transparent rounded-full animate-spin mb-4 mx-auto"></div>
            <p>AI подбирает новый образ...</p>
          </div>
        </div>
      </div>

      <!-- Controls & Inventory -->
      <div class="flex flex-col justify-between">
        <div class="options-group space-y-6">
          <label class="block">
            <span class="text-sm text-slate-400">Цвет / Параметры</span>
            <input type="color" class="w-full h-12 rounded-lg mt-2 bg-slate-800 border-0 cursor-pointer p-0" />
          </label>

          <div class="materials">
            <h3 class="text-sm font-semibold mb-3">Используемые материалы (Auto-detect)</h3>
            <div class="grid grid-cols-2 gap-3">
              <div v-for="i in materials" :key="i.id" class="p-3 bg-slate-800 rounded-lg border border-slate-700 text-xs">
                <span class="block font-medium">{{ i.name }}</span>
                <span class="text-slate-500">{{ i.stock }} в наличии</span>
              </div>
            </div>
          </div>
        </div>

        <div class="actions mt-12 space-y-3">
          <button @click="generate" class="w-full py-4 bg-pink-600 hover:bg-pink-500 rounded-xl font-bold transition shadow-lg shadow-pink-600/20">
            Сгенерировать образ
          </button>
          <button class="w-full py-3 bg-slate-800 hover:bg-slate-700 rounded-xl text-sm transition font-medium">
            Записаться к мастеру
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const activeType = ref('hair');
const resultImage = ref(null);
const loading = ref(false);

const types = [
    { id: 'hair', label: 'Причёска' },
    { id: 'makeup', label: 'Макияж' },
    { id: 'care', label: 'Уход' }
];

const materials = [
    { id: 101, name: 'L`oreal Professionnel Ink', stock: '12 шт' },
    { id: 102, name: 'Kerastase Serum', stock: '5 шт' }
];

const generate = () => {
    loading.value = true;
    setTimeout(() => {
        resultImage.value = 'https://media.glamour.com/photos/5f9b4568f1f727402660a1e0/master/w_2560%2Cc_limit/hair%2520color.jpg';
        loading.value = false;
    }, 3000);
};
</script>