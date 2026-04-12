<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { HomeIcon, MagnifyingGlassIcon, ShoppingBagIcon, UserIcon, Squares2X2Icon, BuildingOffice2Icon } from '@heroicons/vue/24/outline';
import { useTheme, THEMES } from '@/composables/useTheme';

defineProps({
    title: { type: String, default: '' },
    showBack: { type: Boolean, default: false },
    backHref: { type: String, default: '/' },
});

const { currentTheme, setTheme, nextTheme } = useTheme();
const showThemePicker = ref(false);
const headerScrolled = ref(false);

const isMobile = ref(false);
const onResize = () => { isMobile.value = window.innerWidth < 768; };
const onScroll = () => { headerScrolled.value = window.scrollY > 10; };
onMounted(() => {
    onResize();
    onScroll();
    window.addEventListener('resize', onResize);
    window.addEventListener('scroll', onScroll, { passive: true });
    document.addEventListener('click', closePickerOnOutside);
});
onUnmounted(() => {
    window.removeEventListener('resize', onResize);
    window.removeEventListener('scroll', onScroll);
    document.removeEventListener('click', closePickerOnOutside);
});

const page = usePage();
const currentUrl = computed(() => page.url);
const isActive = (href) => currentUrl.value === href || (href !== '/' && currentUrl.value.startsWith(href));

const navLinks = [
    { label: 'Красота', href: '/category/beauty', icon: '💄' },
    { label: 'Рестораны', href: '/category/restaurants', icon: '🍔' },
    { label: 'Отели', href: '/category/hotels', icon: '🏨' },
    { label: 'Мебель', href: '/category/furniture', icon: '🛋️' },
    { label: 'Мода', href: '/category/fashion', icon: '👗' },
    { label: 'Все', href: '/categories', icon: '📂' },
];

const bottomNav = [
    { label: 'Главная', href: '/', icon: HomeIcon },
    { label: 'Поиск', href: '/search', icon: MagnifyingGlassIcon },
    { label: 'Бизнес', href: '/business', icon: BuildingOffice2Icon },
    { label: 'Заказы', href: '/orders', icon: ShoppingBagIcon },
    { label: 'Профиль', href: '/profile', icon: UserIcon },
];

const closePickerOnOutside = (e) => {
    if (showThemePicker.value && !e.target.closest('.theme-picker-area')) {
        showThemePicker.value = false;
    }
};

/* Visual flash on mobile when cycling themes */
const themeFlash = ref(false);
function onNextTheme() {
    nextTheme();
    themeFlash.value = true;
    setTimeout(() => { themeFlash.value = false; }, 400);
}
</script>

<template>
    <div class="min-h-screen transition-colors duration-500" style="background: linear-gradient(to bottom, var(--t-gradient-from), var(--t-gradient-via), var(--t-gradient-to));">

        <!-- ===== HEADER DESKTOP ===== -->
        <header v-if="!isMobile" class="sticky top-0 z-50 backdrop-blur-xl border-b transition-all duration-500"
            :class="headerScrolled ? 'shadow-lg' : ''"
            style="background: var(--t-header); border-color: var(--t-border);">
            <div class="max-w-7xl mx-auto flex items-center justify-between px-6 h-16">
                <Link href="/" class="flex items-center gap-2.5 hover:opacity-80 transition-opacity active:scale-95">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg font-black shadow-lg transition-all duration-500 hover:scale-110"
                         style="background: var(--t-primary-dim); box-shadow: 0 4px 14px var(--t-glow);">🐱</div>
                    <span class="text-base font-black tracking-tight" style="color: var(--t-text);">CatVRF</span>
                </Link>
                <nav class="flex items-center gap-1">
                    <Link v-for="link in navLinks" :key="link.href" :href="link.href"
                        class="px-3 py-1.5 rounded-lg text-sm transition-all duration-300 hover:scale-105 active:scale-95"
                        :style="isActive(link.href)
                            ? { color: 'var(--t-text)', background: 'var(--t-surface)', fontWeight: 700 }
                            : { color: 'var(--t-text-2)' }">
                        {{ link.label }}
                    </Link>
                </nav>
                <div class="flex items-center gap-2">
                    <!-- Business Cabinet link -->
                    <Link href="/business"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-300 hover:scale-105 active:scale-95 border"
                        :style="isActive('/business')
                            ? { background: 'linear-gradient(135deg, #f59e0b22, #d9770622)', borderColor: '#f59e0b44', color: '#fbbf24' }
                            : { background: 'var(--t-surface)', borderColor: 'var(--t-border)', color: 'var(--t-text-2)' }">
                        <BuildingOffice2Icon class="w-4 h-4" />
                        <span>Бизнес</span>
                    </Link>
                    <!-- Переключатель тем -->
                    <div class="relative theme-picker-area">
                        <button @click="showThemePicker = !showThemePicker"
                            class="w-9 h-9 rounded-xl flex items-center justify-center text-sm transition-all duration-300 hover:scale-105 active:scale-95 border"
                            style="background: var(--t-surface); border-color: var(--t-border);"
                            :title="`Тема: ${THEMES.find(t => t.id === currentTheme)?.label}`">
                            {{ THEMES.find(t => t.id === currentTheme)?.short }}
                        </button>
                        <Transition enter-from-class="opacity-0 scale-95 -translate-y-1" enter-active-class="transition duration-200" leave-to-class="opacity-0 scale-95 -translate-y-1" leave-active-class="transition duration-150">
                            <div v-if="showThemePicker" class="absolute right-0 top-12 p-2 rounded-xl border shadow-2xl z-50 min-w-[160px]"
                                style="background: var(--t-bg); border-color: var(--t-border);" @click.stop>
                                <button v-for="t in THEMES" :key="t.id" @click="setTheme(t.id); showThemePicker = false"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-all duration-200 text-left"
                                    :style="currentTheme === t.id
                                        ? { background: 'var(--t-primary-dim)', color: 'var(--t-text)', fontWeight: 700 }
                                        : { color: 'var(--t-text-2)' }">
                                    <span>{{ t.short }}</span>
                                    <span>{{ t.label.replace(t.short + ' ', '') }}</span>
                                </button>
                            </div>
                        </Transition>
                    </div>
                    <Link href="/search" class="w-9 h-9 rounded-xl border flex items-center justify-center transition-colors duration-300"
                        style="background: var(--t-surface); border-color: var(--t-border);">
                        <MagnifyingGlassIcon class="w-4 h-4" style="color: var(--t-text-2);" />
                    </Link>
                    <Link href="/profile" class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-300 text-white hover:scale-105 active:scale-95 shadow-lg"
                        style="background: var(--t-btn); box-shadow: 0 4px 14px var(--t-glow);">
                        Войти
                    </Link>
                </div>
            </div>
        </header>

        <!-- ===== HEADER MOBILE ===== -->
        <header v-else class="sticky top-0 z-50 backdrop-blur-xl border-b transition-all duration-500"
            :class="headerScrolled ? 'shadow-lg' : ''"
            style="background: var(--t-header); border-color: var(--t-border);">
            <div class="flex items-center justify-between px-4 h-14">
                <div class="flex items-center gap-2">
                    <Link v-if="showBack" :href="backHref"
                        class="w-9 h-9 rounded-xl border flex items-center justify-center active:scale-90 transition-all mr-1 hover:bg-(--t-card-hover)"
                        style="background: var(--t-surface); border-color: var(--t-border);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" style="color: var(--t-text);" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <Link v-if="!showBack" href="/" class="flex items-center gap-2 active:scale-95 transition-transform">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm shadow-lg transition-all duration-500"
                             style="background: var(--t-primary-dim); box-shadow: 0 4px 14px var(--t-glow);">🐱</div>
                        <span class="text-sm font-black tracking-tight" style="color: var(--t-text);">CatVRF</span>
                    </Link>
                    <span v-if="showBack && title" class="text-base font-bold truncate" style="color: var(--t-text);">{{ title }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="onNextTheme"
                        class="w-9 h-9 rounded-xl border flex items-center justify-center active:scale-90 transition-all duration-300"
                        :class="themeFlash ? 'ring-2 ring-offset-1 scale-110' : ''"
                        :style="{
                            background: 'var(--t-surface)',
                            borderColor: 'var(--t-border)',
                            '--tw-ring-color': 'var(--t-primary)',
                            '--tw-ring-offset-color': 'var(--t-bg)',
                        }">
                        {{ THEMES.find(t => t.id === currentTheme)?.short }}
                    </button>
                    <Link href="/search" class="w-9 h-9 rounded-xl border flex items-center justify-center active:scale-90 transition-transform"
                        style="background: var(--t-surface); border-color: var(--t-border);">
                        <MagnifyingGlassIcon class="w-4 h-4" style="color: var(--t-text-2);" />
                    </Link>
                </div>
            </div>
        </header>

        <!-- ===== MAIN CONTENT ===== -->
        <main :class="isMobile ? 'pb-24 px-4 pt-4' : 'max-w-7xl mx-auto px-6 py-8'">
            <slot />
        </main>

        <!-- ===== BOTTOM NAV (MOBILE) ===== -->
        <nav v-if="isMobile" class="fixed bottom-0 inset-x-0 z-50 border-t backdrop-blur-xl transition-all duration-500"
            style="background: var(--t-header); border-color: var(--t-border);">
            <div class="flex justify-around items-center h-16 max-w-lg mx-auto">
                <Link v-for="item in bottomNav" :key="item.href" :href="item.href"
                    class="flex flex-col items-center justify-center gap-0.5 w-full h-full transition-all duration-300 active:scale-90"
                    :style="isActive(item.href) ? { color: item.label === 'Бизнес' ? '#fbbf24' : 'var(--t-primary)' } : { color: 'var(--t-text-3)' }">
                    <component :is="item.icon" class="w-5 h-5" />
                    <span class="text-[10px] font-bold uppercase tracking-wider">{{ item.label }}</span>
                </Link>
            </div>
        </nav>

        <!-- ===== FOOTER DESKTOP ===== -->
        <footer v-if="!isMobile" class="border-t mt-16 transition-colors duration-500" style="border-color: var(--t-border);">
            <div class="max-w-7xl mx-auto px-6 py-10 flex items-center justify-between text-sm" style="color: var(--t-text-3);">
                <span>© 2026 CatVRF — AI-маркетплейс нового поколения</span>
                <div class="flex gap-6">
                    <Link href="/categories" class="hover:opacity-70 transition-opacity hover:text-(--t-text) active:scale-95">Категории</Link>
                    <Link href="/business" class="hover:opacity-70 transition-opacity hover:text-amber-400 active:scale-95">Бизнес-кабинет</Link>
                    <Link href="/orders" class="hover:opacity-70 transition-opacity hover:text-(--t-text) active:scale-95">Заказы</Link>
                    <Link href="/profile" class="hover:opacity-70 transition-opacity hover:text-(--t-text) active:scale-95">Профиль</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
