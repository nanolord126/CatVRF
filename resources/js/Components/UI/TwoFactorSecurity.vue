<template>
  <div class="p-6 bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800">
    <h2 class="text-2xl font-black text-slate-800 dark:text-white mb-6 tracking-tight">
      <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-500">
        Безопасность 2026
      </span>
    </h2>

    <div v-if="!enabled" class="space-y-4">
      <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed">
        Усильте защиту вашего аккаунта с помощью 2FA. Используйте Google Authenticator или аналоги.
      </p>
      <button @click="enable2FA" 
        class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl transition-all shadow-lg shadow-indigo-200 active:scale-95">
        Активировать защиту
      </button>
    </div>

    <div v-else class="space-y-6">
      <div v-if="qrCode" class="flex flex-col items-center p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border-2 border-dashed border-slate-200">
        <div v-html="qrCode" class="mb-4"></div>
        <p class="text-xs text-slate-400 text-center uppercase tracking-widest font-bold">Сканируйте QR код</p>
      </div>

      <div v-if="recoveryCodes.length" class="bg-slate-950 p-4 rounded-2xl">
        <p class="text-[10px] text-indigo-400 font-mono mb-2 uppercase tracking-widest">Коды восстановления</p>
        <div class="grid grid-cols-2 gap-2">
          <code v-for="code in recoveryCodes" :key="code" class="text-indigo-100 text-xs font-mono bg-white/5 p-2 rounded-lg text-center">{{ code }}</code>
        </div>
      </div>

      <button @click="disable2FA" 
        class="w-full py-3 border-2 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold rounded-2xl hover:bg-red-50 hover:text-red-600 hover:border-red-100 transition-all">
        Отключить 2FA
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const enabled = ref(false)
const qrCode = ref(null)
const recoveryCodes = ref([])

const enable2FA = async () => {
  // Logic to call Fortify /user/two-factor-authentication via API
  enabled.value = true
}

const disable2FA = async () => {
  // Logic to call DELETE /user/two-factor-authentication
  enabled.value = false
}
</script>
