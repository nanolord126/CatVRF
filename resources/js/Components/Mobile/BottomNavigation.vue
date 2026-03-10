<template>
  <nav class="fixed bottom-0 inset-x-0 bg-white/20 dark:bg-black/40 backdrop-blur-xl border-t border-white/20 pb-[env(safe-area-inset-bottom)] z-50 rounded-t-3xl shadow-[0_-10px_40px_rgba(0,0,0,0.1)]">
    <div class="flex justify-around items-center h-16 max-w-lg mx-auto px-4">
      <Link v-for="item in navItems" :key="item.href" :href="item.href" 
            class="relative flex flex-col items-center justify-center w-full h-full transition-all duration-300 group"
            :class="{ 'scale-110 -translate-y-1': isActive(item.href) }">
        <div class="p-2 rounded-2xl transition-all duration-500 group-hover:bg-primary/10"
             :class="{ 'bg-primary/20 shadow-lg shadow-primary/20': isActive(item.href) }">
          <component :is="item.icon" class="w-6 h-6" :class="isActive(item.href) ? 'text-primary' : 'text-gray-400 group-hover:text-primary/60'" />
        </div>
        <span class="text-[10px] uppercase tracking-widest mt-1 opacity-0 group-hover:opacity-100 transition-opacity font-bold"
              :class="{ 'opacity-100 text-primary': isActive(item.href) }">{{ item.label }}</span>
        <div v-if="isActive(item.href)" class="absolute -top-1 w-1 h-1 bg-primary rounded-full animate-pulse shadow-[0_0_8px_var(--primary)]"></div>
      </Link>
    </div>
  </nav>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { HomeIcon, MagnifyingGlassIcon, ShoppingBagIcon, UserIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';

const navItems = [
  { label: 'Главная', href: '/', icon: HomeIcon },
  { label: 'Поиск', href: '/search', icon: MagnifyingGlassIcon },
  { label: 'Заказы', href: '/orders', icon: ShoppingBagIcon },
  { label: 'Профиль', href: '/profile', icon: UserIcon },
  { label: 'Меню', href: '/categories', icon: Squares2X2Icon },
];

const isActive = (href) => usePage().url === href || (href !== '/' && usePage().url.startsWith(href));
</script>

<style scoped>
.text-primary { color: #3b82f6; } /* Example primary, use your theme variable */
.bg-primary\/20 { background-color: rgba(59, 130, 246, 0.2); }
</style>
