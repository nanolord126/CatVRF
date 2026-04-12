<script setup lang="ts">
/**
 * CatVRF 2026 — VisitDetailModal (Beauty)
 * Детали визита: мастер, услуги, фото до/после, комментарий, рейтинг
 */
import { computed } from 'vue';
import type { BeautyVisit } from '@/types/beauty';
import {
    XMarkIcon,
    CalendarIcon,
    UserIcon,
    CurrencyDollarIcon,
    StarIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon as StarSolidIcon } from '@heroicons/vue/24/solid';

const props = defineProps<{
    visit: BeautyVisit;
}>();

const emit = defineEmits<{
    close: [];
}>();

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function formatPrice(price: number): string {
    return price.toLocaleString('ru-RU') + ' ₽';
}

const statusConfig: Record<string, { label: string; class: string }> = {
    completed: { label: 'Выполнен', class: 'bg-emerald-500 text-white' },
    cancelled: { label: 'Отменён', class: 'bg-red-500 text-white' },
    no_show: { label: 'Не пришёл', class: 'bg-amber-500 text-white' },
};

const statusInfo = computed(() => statusConfig[props.visit.status] ?? { label: props.visit.status, class: 'bg-gray-400 text-white' });
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50" @click="emit('close')" />

            <!-- Modal -->
            <div class="relative z-10 w-full max-w-lg rounded-2xl bg-(--t-surface) p-6 shadow-2xl">
                <!-- Close -->
                <button
                    class="absolute right-4 top-4 rounded-lg p-1 text-(--t-text-secondary) hover:bg-(--t-surface-hover)"
                    @click="emit('close')"
                >
                    <XMarkIcon class="size-5" />
                </button>

                <!-- Header -->
                <div class="mb-6">
                    <div class="mb-2 flex items-center gap-3">
                        <h2 class="text-xl font-bold text-(--t-text)">Детали визита</h2>
                        <span
                            class="rounded-full px-3 py-0.5 text-xs font-medium"
                            :class="statusInfo.class"
                        >
                            {{ statusInfo.label }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-(--t-text-secondary)">
                        <CalendarIcon class="size-4" />
                        {{ formatDate(visit.date) }}
                    </div>
                </div>

                <!-- Master -->
                <div class="mb-4 flex items-center gap-3 rounded-xl bg-(--t-surface-secondary) p-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-(--t-primary)/10">
                        <UserIcon class="size-5 text-(--t-primary)" />
                    </div>
                    <div>
                        <p class="font-medium text-(--t-text)">{{ visit.master_name }}</p>
                        <p class="text-xs text-(--t-text-secondary)">Мастер</p>
                    </div>
                </div>

                <!-- Services -->
                <div class="mb-4">
                    <h3 class="mb-2 text-sm font-semibold text-(--t-text)">Услуги</h3>
                    <div class="space-y-1.5">
                        <div
                            v-for="svc in visit.services"
                            :key="svc.name"
                            class="flex items-center justify-between rounded-lg bg-(--t-surface-secondary) px-3 py-2"
                        >
                            <span class="text-sm text-(--t-text)">{{ svc.name }}</span>
                            <span class="text-sm font-medium text-(--t-text)">{{ formatPrice(svc.price) }}</span>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between border-t border-(--t-border) pt-3">
                        <span class="text-sm font-semibold text-(--t-text)">Итого</span>
                        <span class="text-lg font-bold text-(--t-primary)">{{ formatPrice(visit.total) }}</span>
                    </div>
                </div>

                <!-- Rating -->
                <div v-if="visit.rating" class="mb-4">
                    <h3 class="mb-2 text-sm font-semibold text-(--t-text)">Оценка</h3>
                    <div class="flex items-center gap-1">
                        <component
                            :is="i <= (visit.rating ?? 0) ? StarSolidIcon : StarIcon"
                            v-for="i in 5"
                            :key="i"
                            class="size-5"
                            :class="i <= (visit.rating ?? 0) ? 'text-amber-400' : 'text-gray-300'"
                        />
                        <span class="ml-2 text-sm text-(--t-text-secondary)">{{ visit.rating }} / 5</span>
                    </div>
                </div>

                <!-- Comment -->
                <div v-if="visit.comment" class="mb-4">
                    <h3 class="mb-2 text-sm font-semibold text-(--t-text)">Комментарий</h3>
                    <p class="rounded-xl bg-(--t-surface-secondary) p-3 text-sm text-(--t-text-secondary)">
                        {{ visit.comment }}
                    </p>
                </div>

                <!-- Photos -->
                <div v-if="visit.photos.length > 0" class="mb-4">
                    <h3 class="mb-2 text-sm font-semibold text-(--t-text)">
                        Фото ({{ visit.photos.length }})
                    </h3>
                    <div class="grid grid-cols-3 gap-2">
                        <img
                            v-for="(photo, idx) in visit.photos"
                            :key="idx"
                            :src="photo"
                            class="aspect-square rounded-lg object-cover"
                            :alt="`Фото визита ${idx + 1}`"
                        />
                    </div>
                </div>

                <!-- Close button -->
                <button
                    class="mt-2 w-full rounded-xl bg-(--t-primary) py-2.5 text-sm font-medium text-white hover:bg-(--t-primary)/90"
                    @click="emit('close')"
                >
                    Закрыть
                </button>
            </div>
        </div>
    </Teleport>
</template>
