<script setup lang="ts">
/**
 * CatVRF 2026 — TaxiRideCard
 * Карточка поездки: маршрут, пассажир, статус, цена, рейтинг
 */
import type { TaxiRide } from '@/types/taxi';
import { MapPinIcon, ClockIcon, CurrencyDollarIcon } from '@heroicons/vue/24/outline';
import { StarIcon as StarSolidIcon } from '@heroicons/vue/24/solid';

const props = defineProps<{
    ride: TaxiRide;
}>();

const emit = defineEmits<{
    select: [ride: TaxiRide];
}>();

function statusColor(status: TaxiRide['status']): string {
    switch (status) {
        case 'searching': return 'bg-amber-500';
        case 'accepted': return 'bg-blue-500';
        case 'arrived': return 'bg-indigo-500';
        case 'in_progress': return 'bg-violet-500';
        case 'completed': return 'bg-emerald-500';
        case 'cancelled': return 'bg-red-500';
        default: return 'bg-gray-400';
    }
}

function statusLabel(status: TaxiRide['status']): string {
    switch (status) {
        case 'searching': return 'Поиск';
        case 'accepted': return 'Принят';
        case 'arrived': return 'На месте';
        case 'in_progress': return 'В пути';
        case 'completed': return 'Завершён';
        case 'cancelled': return 'Отменён';
        default: return status;
    }
}

function formatMoney(amount: number): string {
    return amount.toLocaleString('ru-RU') + ' ₽';
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <div
        class="cursor-pointer rounded-2xl border border-(--t-border) bg-(--t-surface) p-4 transition-shadow hover:shadow-md"
        @click="emit('select', ride)"
    >
        <!-- Header: status + date -->
        <div class="mb-3 flex items-center justify-between">
            <span
                class="rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
                :class="statusColor(ride.status)"
            >
                {{ statusLabel(ride.status) }}
            </span>
            <span class="text-xs text-(--t-text-secondary)">{{ formatDate(ride.created_at) }}</span>
        </div>

        <!-- Route: pickup → dropoff -->
        <div class="mb-3 space-y-2">
            <div class="flex items-start gap-2">
                <div class="mt-0.5 size-3 shrink-0 rounded-full bg-emerald-500" />
                <p class="text-sm text-(--t-text)">{{ ride.pickup_address }}</p>
            </div>
            <div class="ml-1.5 h-4 w-0.5 bg-(--t-border)" />
            <div class="flex items-start gap-2">
                <div class="mt-0.5 size-3 shrink-0 rounded-full bg-red-500" />
                <p class="text-sm text-(--t-text)">{{ ride.dropoff_address }}</p>
            </div>
        </div>

        <!-- Stats: distance, duration, fare -->
        <div class="mb-3 flex items-center gap-4 text-xs text-(--t-text-secondary)">
            <span class="flex items-center gap-1">
                <MapPinIcon class="size-4" />
                {{ ride.distance_km.toFixed(1) }} км
            </span>
            <span class="flex items-center gap-1">
                <ClockIcon class="size-4" />
                {{ ride.duration_minutes }} мин
            </span>
            <span class="flex items-center gap-1">
                <CurrencyDollarIcon class="size-4" />
                {{ ride.payment_method === 'cash' ? 'Наличные' : ride.payment_method === 'card' ? 'Карта' : 'Кошелёк' }}
            </span>
        </div>

        <!-- Footer: passenger, fare, rating -->
        <div class="flex items-center justify-between border-t border-(--t-border) pt-3">
            <div>
                <p class="text-sm font-medium text-(--t-text)">{{ ride.passenger_name }}</p>
                <p class="text-xs text-(--t-text-secondary)">{{ ride.passenger_phone }}</p>
            </div>
            <div class="text-right">
                <p class="text-lg font-bold text-(--t-text)">{{ formatMoney(ride.fare) }}</p>
                <div v-if="ride.rating" class="flex items-center justify-end gap-0.5">
                    <StarSolidIcon
                        v-for="i in 5"
                        :key="i"
                        class="size-3"
                        :class="i <= (ride.rating ?? 0) ? 'text-amber-400' : 'text-gray-300'"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
