\<template>
  <div class="p-6 bg-gray-900/80 backdrop-blur-xl text-white rounded-2xl shadow-2xl border border-white/5 relative overflow-hidden group">
    <div class="flex justify-between items-center mb-6 relative z-10">
      <div>
        <h2 class="text-2xl font-black bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-500 bg-clip-text text-transparent uppercase tracking-tighter">
          AI Дизайнер Интерьера 2026
        </h2>
      </div>
    </div>
    <div v-if="!sessionContent" class="upload-zone border-2 border-dashed border-white/10 rounded-2xl p-12 text-center hover:border-blue-500/50 transition-all cursor-pointer bg-white/5 group" @click="simulateUpload">
      <h3 class="text-lg font-bold">Загрузите пространство</h3>
      <p class="text-sm text-gray-400 mt-2 italic text-center text-russian">AI проанализирует габариты и предложит готовый проект</p>
    </div>
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6 relative z-10">
       <!-- Result -->
       <button @click="submitOrder" class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl font-black uppercase text-[10px] tracking-widest">
         Оформить проект (+5% AI Fee)
       </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
const sessionContent = ref(null);
const simulateUpload = () => {
    sessionContent.value = { analysis: { style: "Минимализм 2026" } };
};
const submitOrder = () => { alert("Заказ сформирован"); };
</script>\
