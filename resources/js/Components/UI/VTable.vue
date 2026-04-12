<script setup>
/**
 * VTable — интерактивная таблица с кликабельными строками,
 * hover, сортировкой, loading, пустым состоянием.
 */
import { ref, computed } from 'vue';

const props = defineProps({
    columns: { type: Array, required: true },
    // [{ key: 'name', label: 'Имя', sortable: true, align: 'left' }]
    rows: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    clickableRows: { type: Boolean, default: true },
    striped: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
    emptyText: { type: String, default: 'Данные не найдены' },
    emptyIcon: { type: String, default: '📭' },
});

const emit = defineEmits(['row-click', 'sort']);

const sortKey = ref('');
const sortDir = ref('asc');

function toggleSort(col) {
    if (!col.sortable) return;
    if (sortKey.value === col.key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = col.key;
        sortDir.value = 'asc';
    }
    emit('sort', { key: sortKey.value, direction: sortDir.value });
}

const sortedRows = computed(() => {
    if (!sortKey.value) return props.rows;
    return [...props.rows].sort((a, b) => {
        const va = a[sortKey.value];
        const vb = b[sortKey.value];
        const dir = sortDir.value === 'asc' ? 1 : -1;
        if (typeof va === 'number') return (va - vb) * dir;
        return String(va).localeCompare(String(vb)) * dir;
    });
});

const alignClass = (align) => ({
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
}[align || 'left']);
</script>

<template>
    <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl overflow-hidden">
        <!-- Loading bar -->
        <div v-if="loading" class="h-0.5 bg-(--t-primary) animate-pulse rounded-full" />

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-(--t-border)">
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            :class="[
                                alignClass(col.align),
                                compact ? 'px-3 py-2 text-xs' : 'px-4 py-3 text-xs',
                                'font-semibold uppercase tracking-wider text-(--t-text-3)',
                                col.sortable ? 'cursor-pointer hover:text-(--t-text) select-none transition-colors group/th' : '',
                            ]"
                            @click="toggleSort(col)"
                        >
                            <span class="inline-flex items-center gap-1">
                                {{ col.label }}
                                <span v-if="col.sortable" class="opacity-0 group-hover/th:opacity-50 transition-opacity">
                                    <template v-if="sortKey === col.key">
                                        {{ sortDir === 'asc' ? '↑' : '↓' }}
                                    </template>
                                    <template v-else>↕</template>
                                </span>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loading skeleton -->
                    <template v-if="loading && rows.length === 0">
                        <tr v-for="i in 5" :key="'sk-'+i">
                            <td v-for="col in columns" :key="col.key" :class="compact ? 'px-3 py-2' : 'px-4 py-3'">
                                <div class="h-4 bg-(--t-border) rounded animate-pulse" :style="{ width: (40 + Math.random()*40) + '%' }" />
                            </td>
                        </tr>
                    </template>

                    <!-- Empty state -->
                    <tr v-else-if="!loading && rows.length === 0">
                        <td :colspan="columns.length" class="px-4 py-12 text-center">
                            <div class="text-4xl mb-2">{{ emptyIcon }}</div>
                            <div class="text-sm text-(--t-text-3)">{{ emptyText }}</div>
                        </td>
                    </tr>

                    <!-- Rows -->
                    <tr
                        v-else
                        v-for="(row, idx) in sortedRows"
                        :key="row.id || idx"
                        :class="[
                            'border-b border-(--t-border)/50 transition-colors duration-150',
                            clickableRows ? 'cursor-pointer hover:bg-(--t-card-hover) active:bg-(--t-primary-dim)' : '',
                            striped && idx % 2 ? 'bg-(--t-surface)' : '',
                        ]"
                        @click="clickableRows && emit('row-click', row)"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            :class="[alignClass(col.align), compact ? 'px-3 py-2 text-xs' : 'px-4 py-3 text-sm', 'text-(--t-text)']"
                        >
                            <slot :name="'cell-' + col.key" :row="row" :value="row[col.key]">
                                {{ row[col.key] }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
