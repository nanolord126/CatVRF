<script setup lang="ts">
/**
 * CatVRF 2026 — TenantSwitcher
 * Компонент переключения между тенантами/филиалами (BusinessGroup)
 */
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useAuth } from '@/stores/core/useAuth';
import { ChevronUpDownIcon, BuildingOfficeIcon, CheckIcon } from '@heroicons/vue/24/outline';
import type { TenantSwitchItem } from '@/types/tenant';
import apiClient from '@/api/apiClient';

const auth = useAuth();
const isOpen = ref(false);
const tenants = ref<TenantSwitchItem[]>([]);
const isLoading = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const currentLabel = computed(() => {
    if (auth.businessGroup) return auth.businessGroup.legal_name;
    return auth.tenantName || 'Выберите бизнес';
});

async function fetchTenants(): Promise<void> {
    isLoading.value = true;
    try {
        const { data } = await apiClient.get<{ data: TenantSwitchItem[] }>('/tenants/available');
        tenants.value = data.data ?? [];
    } catch {
        /* silent */
    } finally {
        isLoading.value = false;
    }
}

async function switchTenant(tenantId: number): Promise<void> {
    try {
        await apiClient.post(`/tenants/${tenantId}/switch`);
        window.location.reload();
    } catch {
        /* silent */
    }
}

function toggle(): void {
    isOpen.value = !isOpen.value;
    if (isOpen.value && tenants.value.length === 0) {
        fetchTenants();
    }
}

function onClickOutside(event: MouseEvent): void {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        isOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside));
</script>

<template>
    <div ref="dropdownRef" class="relative hidden sm:block">
        <button
            class="flex items-center gap-2 rounded-lg border border-(--t-border) bg-(--t-surface-secondary) px-3 py-1.5 text-sm text-(--t-text) hover:bg-(--t-surface-hover)"
            @click="toggle"
        >
            <BuildingOfficeIcon class="size-4 shrink-0 text-(--t-text-secondary)" />
            <span class="max-w-32 truncate">{{ currentLabel }}</span>
            <ChevronUpDownIcon class="size-4 shrink-0 text-(--t-text-secondary)" />
        </button>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 top-full z-50 mt-2 w-64 rounded-xl border border-(--t-border) bg-(--t-surface) p-2 shadow-xl"
            >
                <div v-if="isLoading" class="flex items-center justify-center py-4">
                    <div class="size-5 animate-spin rounded-full border-2 border-(--t-primary) border-t-transparent" />
                </div>

                <template v-else>
                    <button
                        v-for="t in tenants"
                        :key="t.id"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm hover:bg-(--t-surface-hover)"
                        @click="switchTenant(t.id)"
                    >
                        <img
                            v-if="t.logo_url"
                            :src="t.logo_url"
                            class="size-8 rounded-lg object-cover"
                            :alt="t.name"
                        />
                        <div v-else class="flex size-8 items-center justify-center rounded-lg bg-(--t-primary)/10">
                            <BuildingOfficeIcon class="size-4 text-(--t-primary)" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-(--t-text)">{{ t.name }}</p>
                            <p class="truncate text-xs text-(--t-text-secondary)">{{ t.vertical }}</p>
                        </div>
                        <CheckIcon
                            v-if="auth.tenant?.id === t.id"
                            class="size-4 shrink-0 text-(--t-primary)"
                        />
                    </button>

                    <p v-if="tenants.length === 0" class="py-4 text-center text-sm text-(--t-text-secondary)">
                        Нет доступных бизнесов
                    </p>
                </template>
            </div>
        </Transition>
    </div>
</template>
