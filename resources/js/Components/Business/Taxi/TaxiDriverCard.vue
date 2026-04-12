<script setup lang="ts">
/**
 * CatVRF 2026 — TaxiDriverCard
 * Карточка водителя такси: аватар, рейтинг, статус, авто, статистика
 */
import type { TaxiDriver } from '@/types/taxi';
import {
    StarIcon,
    PhoneIcon,
    MapPinIcon,
    TruckIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon as StarSolidIcon } from '@heroicons/vue/24/solid';

const props = defineProps<{
    driver: TaxiDriver;
}>();

const emit = defineEmits<{
    select: [driver: TaxiDriver];
    call: [driver: TaxiDriver];
    locate: [driver: TaxiDriver];
}>();

function statusColor(status: TaxiDriver['status']): string {
    switch (status) {
        case 'available': return 'bg-emerald-500';
        case 'on_ride': return 'bg-blue-500';
        case 'offline': return 'bg-gray-400';
        case 'blocked': return 'bg-red-500';
        default: return 'bg-gray-400';
    }
}

function statusLabel(status: TaxiDriver['status']): string {
    switch (status) {
        case 'available': return 'Свободен';
        case 'on_ride': return 'На заказе';
        case 'offline': return 'Оффлайн';
        case 'blocked': return 'Заблокирован';
        default: return status;
    }
}

function formatBalance(balance: number): string {
    return balance.toLocaleString('ru-RU') + ' ₽';
}
</script>

<template>
    <div
        class="cursor-pointer rounded-2xl border border-(--t-border) bg-(--t-surface) p-4 transition-shadow hover:shadow-lg"
        @click="emit('select', driver)"
    >
        <!-- Header: avatar + info + status -->
        <div class="mb-3 flex items-start gap-3">
            <div class="relative">
                <img
                    v-if="driver.avatar_url"
                    :src="driver.avatar_url"
                    :alt="driver.full_name"
                    class="size-14 rounded-full object-cover"
                />
                <div
                    v-else
                    class="flex size-14 items-center justify-center rounded-full bg-(--t-primary)/10 text-lg font-bold text-(--t-primary)"
                >
                    {{ driver.full_name.charAt(0) }}
                </div>
                <!-- Online indicator -->
                <div
                    class="absolute -bottom-0.5 -right-0.5 size-4 rounded-full border-2 border-(--t-surface)"
                    :class="driver.is_online ? 'bg-emerald-500' : 'bg-gray-400'"
                />
            </div>

            <div class="min-w-0 flex-1">
                <h3 class="truncate font-semibold text-(--t-text)">{{ driver.full_name }}</h3>
                <div class="mt-0.5 flex items-center gap-1">
                    <StarSolidIcon class="size-4 text-amber-400" />
                    <span class="text-sm text-(--t-text)">{{ driver.rating.toFixed(1) }}</span>
                    <span class="text-xs text-(--t-text-secondary)">({{ driver.reviews_count }})</span>
                </div>
            </div>

            <span
                class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium text-white"
                :class="statusColor(driver.status)"
            >
                {{ statusLabel(driver.status) }}
            </span>
        </div>

        <!-- Vehicle info -->
        <div class="mb-3 flex items-center gap-2 rounded-xl bg-(--t-surface-secondary) p-2.5">
            <TruckIcon class="size-5 shrink-0 text-(--t-text-secondary)" />
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm text-(--t-text)">
                    {{ driver.vehicle.brand }} {{ driver.vehicle.model }}
                </p>
                <p class="text-xs text-(--t-text-secondary)">
                    {{ driver.vehicle.plate_number }} · {{ driver.vehicle.color }} · {{ driver.vehicle.vehicle_class }}
                </p>
            </div>
        </div>

        <!-- Stats row -->
        <div class="mb-3 grid grid-cols-3 gap-2 text-center">
            <div>
                <p class="text-lg font-bold text-(--t-text)">{{ driver.total_rides }}</p>
                <p class="text-xs text-(--t-text-secondary)">Поездок</p>
            </div>
            <div>
                <p class="text-lg font-bold text-(--t-text)">{{ formatBalance(driver.balance) }}</p>
                <p class="text-xs text-(--t-text-secondary)">Баланс</p>
            </div>
            <div>
                <p class="text-lg font-bold text-(--t-text)">{{ driver.rating.toFixed(1) }}</p>
                <p class="text-xs text-(--t-text-secondary)">Рейтинг</p>
            </div>
        </div>

        <!-- Tags -->
        <div v-if="driver.tags.length > 0" class="mb-3 flex flex-wrap gap-1">
            <span
                v-for="tag in driver.tags"
                :key="tag"
                class="rounded-lg bg-(--t-primary)/10 px-2 py-0.5 text-xs text-(--t-primary)"
            >
                {{ tag }}
            </span>
        </div>

        <!-- Action buttons -->
        <div class="flex gap-2">
            <button
                class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-(--t-primary) py-2 text-sm font-medium text-white hover:bg-(--t-primary)/90"
                @click.stop="emit('call', driver)"
            >
                <PhoneIcon class="size-4" />
                Позвонить
            </button>
            <button
                v-if="driver.current_location"
                class="flex items-center justify-center rounded-xl border border-(--t-border) px-3 py-2 text-(--t-text-secondary) hover:bg-(--t-surface-hover)"
                @click.stop="emit('locate', driver)"
            >
                <MapPinIcon class="size-5" />
            </button>
        </div>
    </div>
</template>
