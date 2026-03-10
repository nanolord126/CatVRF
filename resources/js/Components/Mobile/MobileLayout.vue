<template>
  <div class="min-h-screen bg-[#050510] text-gray-200 font-sans tracking-tight antialiased">
    <!-- Header: Blur Dynamic 2026 -->
    <header class="sticky top-0 z-50 bg-black/40 backdrop-blur-3xl border-b border-white/10 px-6 py-4 flex items-center justify-between pointer-events-auto">
      <div class="flex items-center space-x-2">
        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-900 rounded-2xl shadow-indigo-500/20 shadow-2xl animate-pulse"></div>
        <span class="text-2xl font-black uppercase tracking-tighter text-white">CAT-VRF</span>
      </div>
      <div class="flex items-center space-x-4">
        <button class="flex items-center space-x-2 px-3 py-1.5 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition-all group">
          <div class="w-2.5 h-2.5 bg-blue-500 rounded-full group-hover:animate-ping"></div>
          <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 group-hover:text-white">Style: 2026</span>
        </button>
        <div class="relative group">
          <BellIcon class="w-7 h-7 text-gray-400 group-hover:text-blue-500 transition-colors" />
          <span class="absolute top-0 right-0 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-black"></span>
        </div>
      </div>
    </header>

    <!-- Main Content Container with Bottom Nav offset -->
    <main class="relative z-10 p-6 pb-[120px] transition-all duration-700 animate-in fade-in slide-in-from-bottom-6">
      <div class="max-w-7xl mx-auto space-y-12">
        <slot />
      </div>
    </main>

    <!-- Navigation Fixed -->
    <BottomNavigation />
    <InstallPWA />
    
    <!-- Deep Noise Background Texture -->
    <div class="fixed inset-0 pointer-events-none opacity-[0.03] bg-[url('https://grainy-gradients.vercel.app/noise.svg')] z-0"></div>
    <div class="fixed top-0 left-1/4 w-[500px] h-[500px] bg-blue-600/30 blur-[150px] rounded-full z-[-1]"></div>
    <div class="fixed bottom-0 right-1/4 w-[500px] h-[500px] bg-purple-600/20 blur-[150px] rounded-full z-[-1]"></div>
  </div>
</template>

<script setup>
import { BellIcon } from '@heroicons/vue/24/outline';
import BottomNavigation from './BottomNavigation.vue';
import InstallPWA from './InstallPWA.vue';

// 2026 Fluid Typography base script (optional but recommended for 100% responsiveness)
// const updateFluidSize = () => { document.documentElement.style.setProperty('--fluid-size', `${window.innerWidth / 100}px`); };
// onMounted(() => { updateFluidSize(); window.addEventListener('resize', updateFluidSize); });
</script>

<style>
:root { font-size: 16px; scroll-behavior: smooth; }
@media (max-width: 640px) { :root { font-size: 14px; } }
.animate-in { animation: enter 0.8s ease-out; }
@keyframes enter { from { opacity: 0; transform: translateY(20px); } }
</style>
