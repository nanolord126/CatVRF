<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import MobileLayout from '@/Components/Mobile/MobileLayout.vue';
import ProductCard3D from '@/Components/Mobile/ProductCard3D.vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

const isMobile = ref(false);
onMounted(() => {
    isMobile.value = window.innerWidth < 768;
    window.addEventListener('resize', () => {
        isMobile.value = window.innerWidth < 768;
    });
});
const searchQuery = ref('');
const categories = [
    { name: 'Цветы', icon: '🌸', slug: 'flowers', count: 124 },
    { name: 'Рестораны', icon: '🍕', slug: 'restaurants', count: 86 },
];

const mobileCategories = ['Events', 'Hotels', 'Food', 'Taxi', 'Gym', 'Clinics'];

const products = [
  { id: 1, name: 'Exclusive Billiards Lounge', price: '2,500', category: 'Events', image: 'https://images.unsplash.com/photo-1549413240-3d71249b6574?q=80&w=2070&auto=format&fit=crop' },
  { id: 2, name: 'Cyberpunk Karaoke VIP', price: '4,200', category: 'Karaoke', image: 'https://images.unsplash.com/photo-1508700115892-45ecd05ae2ad?q=80&w=2069&auto=format&fit=crop' },
];
</script>

<template>
    <Head title="CatVRF 2026 Ecosystem" />

    <MobileLayout v-if="isMobile">
        <section class="space-y-6">
            <h1 class="text-5xl font-black text-white leading-none tracking-tighter italic uppercase">
                Твой <br/> <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-600 underline text-russian">Мир 2026.</span>
            </h1>
            <div class="relative group mt-8">
                <input type="text" placeholder="Поиск впечатлений..." class="block w-full pl-6 pr-4 py-5 bg-white/5 border border-white/10 rounded-2xl text-white outline-none backdrop-blur-3xl text-russian">
            </div>
        </section>
        <section class="space-y-6 mt-16">
            <h2 class="text-2xl font-black text-white uppercase tracking-tighter text-russian">Рекомендации AI</h2>
            <div class="grid grid-cols-1 gap-10">
                <ProductCard3D v-for="product in products" :key="product.id" :product="product" />
            </div>
        </section>
    </MobileLayout>

    <div v-else class="min-h-screen bg-black overflow-hidden relative">
        <nav class="relative z-10 flex items-center justify-between p-6 px-12 backdrop-blur-md border-b border-white/5 bg-black/50">
            <div class="flex items-center space-x-3 text-white font-black italic">CatVRF Ecosystem</div>
            <a href="/admin" class="px-6 py-2.5 bg-white text-black rounded-full font-black uppercase text-xs active:scale-95 transition-all">Вход</a>
        </nav>
        <main class="relative z-10 pt-24 text-center">
            <h1 class="text-8xl font-black text-white uppercase">Build the <span class="text-purple-500">future</span></h1>
        </main>
    </div>
</template>

<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

