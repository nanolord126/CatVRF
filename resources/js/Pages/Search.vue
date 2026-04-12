<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { ref, computed } from 'vue';
import { allVerticals, megaCategories } from '@/data/verticals.js';

const searchQuery = ref('');
const isFocused = ref(false);
const hoveredCard = ref(null);
const recentSearches = ['Салон красоты', 'Пицца доставка', 'Фитнес зал', 'Маникюр', 'Суши', 'Ветклиника', 'Юрист'];

const setQuery = (q) => {
    searchQuery.value = q;
};

const clearQuery = () => {
    searchQuery.value = '';
};

const filteredVerticals = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return allVerticals.slice(0, 16);
    return allVerticals.filter(v =>
        v.name.toLowerCase().includes(q) || v.categoryName.toLowerCase().includes(q)
    );
});

const filteredRecent = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return recentSearches;
    return recentSearches.filter(s => s.toLowerCase().includes(q));
});

const quickLinks = [
    { label: '🏢 Бизнес-кабинет', href: '/business', highlight: true },
    { label: '🤖 AI-конструкторы', href: '/business?section=ai' },
    { label: '📦 Мои заказы', href: '/orders' },
];
</script>

<template>
    <Head title="Поиск — CatVRF" />

    <AppLayout show-back title="Поиск">
        <section class="space-y-6 max-w-2xl mx-auto">
            <h1 class="text-2xl md:text-3xl font-black" style="color: var(--t-text);">🔍 Поиск</h1>

            <!-- Search Input -->
            <div class="relative">
                <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none transition-colors"
                    :style="{ color: isFocused ? 'var(--t-primary)' : 'var(--t-text-3)' }" />
                <input v-model="searchQuery" type="text" placeholder="Найти салон, ресторан, услугу..."
                    @focus="isFocused = true" @blur="isFocused = false"
                    class="w-full pl-12 pr-10 py-3.5 rounded-xl text-sm outline-none transition-all duration-500"
                    :style="{
                        background: 'var(--t-surface)',
                        border: isFocused ? '2px solid var(--t-primary)' : '1px solid var(--t-border)',
                        color: 'var(--t-text)',
                        boxShadow: isFocused ? '0 0 20px var(--t-glow)' : 'none',
                    }" autofocus>
                <button v-if="searchQuery"
                    @click="clearQuery"
                    class="absolute right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full flex items-center justify-center hover:scale-110 active:scale-90 transition-all cursor-pointer"
                    style="background: var(--t-primary-dim); color: var(--t-text-2);">
                    <XMarkIcon class="w-3.5 h-3.5" />
                </button>
            </div>

            <!-- Quick Links -->
            <div class="flex gap-2 overflow-x-auto pb-1">
                <Link v-for="link in quickLinks" :key="link.label" :href="link.href"
                    class="shrink-0 px-3 py-2 rounded-lg text-xs font-bold border transition-all active:scale-95 hover:scale-105 cursor-pointer whitespace-nowrap"
                    :class="link.highlight ? 'border-amber-500/30 text-amber-400 hover:bg-amber-500/10' : ''"
                    :style="!link.highlight ? { background: 'var(--t-surface)', borderColor: 'var(--t-border)', color: 'var(--t-text-2)' } : {}">
                    {{ link.label }}
                </Link>
            </div>

            <!-- Recent Searches -->
            <div v-if="filteredRecent.length" class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-widest" style="color: var(--t-text-3);">Недавние запросы</h3>
                <div class="flex flex-wrap gap-2">
                    <button v-for="q in filteredRecent" :key="q" @click="setQuery(q)"
                        class="px-4 py-2 rounded-lg text-sm active:scale-90 hover:scale-105 transition-all duration-300 border cursor-pointer hover:shadow-md"
                        style="background: var(--t-surface); border-color: var(--t-border); color: var(--t-text-2);">
                        {{ q }}
                    </button>
                </div>
            </div>

            <!-- Verticals Grid -->
            <div class="space-y-3 mt-6">
                <h3 class="text-xs font-bold uppercase tracking-widest" style="color: var(--t-text-3);">
                    {{ searchQuery.trim() ? `Найдено: ${filteredVerticals.length}` : 'Популярные вертикали' }}
                </h3>
                <div v-if="filteredVerticals.length" class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <Link v-for="v in filteredVerticals" :key="v.slug" :href="`/category/${v.slug}`"
                        @mouseenter.native="hoveredCard = v.slug"
                        @mouseleave.native="hoveredCard = null"
                        class="flex items-center gap-2 p-3 rounded-lg border active:scale-95 hover:scale-[1.02] transition-all duration-300 cursor-pointer hover:shadow-lg search-card"
                        :style="{
                            background: hoveredCard === v.slug ? 'var(--t-card-hover)' : 'var(--t-surface)',
                            borderColor: hoveredCard === v.slug ? 'var(--t-primary)' : 'var(--t-border)',
                        }">
                        <span class="text-lg transition-transform" :class="hoveredCard === v.slug ? 'scale-125' : ''">{{ v.icon }}</span>
                        <div class="min-w-0">
                            <div class="text-xs font-bold truncate" style="color: var(--t-text);">{{ v.name }}</div>
                            <div class="text-[10px] truncate" style="color: var(--t-text-3);">{{ v.categoryName }}</div>
                        </div>
                    </Link>
                </div>
                <div v-else class="text-center py-8">
                    <p class="text-4xl mb-3">🔎</p>
                    <p class="text-sm" style="color: var(--t-text-3);">Ничего не найдено. Попробуйте другой запрос.</p>
                </div>
            </div>
        </section>
    </AppLayout>
</template>

<style scoped>
.search-card:hover {
    box-shadow: 0 0 16px var(--t-glow);
}
</style>