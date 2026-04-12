<script setup lang="ts">
/**
 * CatVRF 2026 — VisitHistoryTimeline (Beauty)
 * Таймлайн визитов клиента салона: дата, мастер, услуги, сумма, рейтинг
 */
import { computed } from 'vue';
import type { BeautyVisit } from '@/types/beauty';
import {
    CalendarIcon,
    StarIcon,
    CameraIcon,
    ChatBubbleLeftEllipsisIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon as StarSolidIcon } from '@heroicons/vue/24/solid';

const props = defineProps<{
    visits: BeautyVisit[];
    isLoading?: boolean;
}>();

const emit = defineEmits<{
    'select-visit': [visit: BeautyVisit];
}>();

const sortedVisits = computed(() =>
    [...props.visits].sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime()),
);

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

function statusColor(status: BeautyVisit['status']): string {
    switch (status) {
        case 'completed': return 'bg-emerald-500';
        case 'cancelled': return 'bg-red-500';
        case 'no_show': return 'bg-amber-500';
        default: return 'bg-gray-400';
    }
}

function statusLabel(status: BeautyVisit['status']): string {
    switch (status) {
        case 'completed': return 'Выполнен';
        case 'cancelled': return 'Отменён';
        case 'no_show': return 'Не пришёл';
        default: return status;
    }
}
</script>

<template>
    <div class="relative">
        <!-- Loading state -->
        <div v-if="isLoading" class="flex flex-col gap-6">
            <div v-for="i in 3" :key="i" class="flex gap-4">
                <div class="size-3 animate-pulse rounded-full bg-(--t-surface-secondary)" />
                <div class="flex-1 animate-pulse rounded-xl bg-(--t-surface-secondary)" style="height: 6rem" />
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-else-if="sortedVisits.length === 0"
            class="py-12 text-center text-sm text-(--t-text-secondary)"
        >
            Нет визитов
        </div>

        <!-- Timeline -->
        <div v-else class="relative pl-6">
            <!-- Vertical line -->
            <div class="absolute bottom-0 left-[5px] top-0 w-0.5 bg-(--t-border)" />

            <div
                v-for="visit in sortedVisits"
                :key="visit.id"
                class="relative mb-6 last:mb-0"
            >
                <!-- Dot -->
                <div
                    class="absolute -left-6 top-2 size-3 rounded-full ring-2 ring-(--t-surface)"
                    :class="statusColor(visit.status)"
                />

                <!-- Card -->
                <button
                    class="w-full rounded-xl border border-(--t-border) bg-(--t-surface) p-4 text-left transition-shadow hover:shadow-md"
                    @click="emit('select-visit', visit)"
                >
                    <!-- Header: date + status -->
                    <div class="mb-2 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-xs text-(--t-text-secondary)">
                            <CalendarIcon class="size-4" />
                            {{ formatDate(visit.date) }}
                        </div>
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-medium text-white"
                            :class="statusColor(visit.status)"
                        >
                            {{ statusLabel(visit.status) }}
                        </span>
                    </div>

                    <!-- Master -->
                    <p class="mb-1 text-sm font-medium text-(--t-text)">
                        {{ visit.master_name }}
                    </p>

                    <!-- Services -->
                    <div class="mb-2 flex flex-wrap gap-1">
                        <span
                            v-for="svc in visit.services"
                            :key="svc.name"
                            class="rounded-lg bg-(--t-surface-secondary) px-2 py-0.5 text-xs text-(--t-text-secondary)"
                        >
                            {{ svc.name }}
                        </span>
                    </div>

                    <!-- Footer: price + rating + photos -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-(--t-text)">
                            {{ formatPrice(visit.total) }}
                        </span>
                        <div class="flex items-center gap-3">
                            <!-- Photos indicator -->
                            <span
                                v-if="visit.photos.length > 0"
                                class="flex items-center gap-1 text-xs text-(--t-text-secondary)"
                            >
                                <CameraIcon class="size-4" />
                                {{ visit.photos.length }}
                            </span>
                            <!-- Comment indicator -->
                            <ChatBubbleLeftEllipsisIcon
                                v-if="visit.comment"
                                class="size-4 text-(--t-text-secondary)"
                            />
                            <!-- Rating -->
                            <div v-if="visit.rating" class="flex items-center gap-0.5">
                                <component
                                    :is="i <= (visit.rating ?? 0) ? StarSolidIcon : StarIcon"
                                    v-for="i in 5"
                                    :key="i"
                                    class="size-3.5"
                                    :class="i <= (visit.rating ?? 0) ? 'text-amber-400' : 'text-gray-300'"
                                />
                            </div>
                        </div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>
