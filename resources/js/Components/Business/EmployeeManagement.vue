<script setup>
/**
 * EmployeeManagement — управление персоналом, графиками, KPI, зарплатами.
 * Интеграция с WalletService для выплат.
 */
import { ref } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VTabs from '../UI/VTabs.vue';
import VStatCard from '../UI/VStatCard.vue';
import VModal from '../UI/VModal.vue';
import VInput from '../UI/VInput.vue';
import VTable from '../UI/VTable.vue';

const activeTab = ref('team');
const tabs = [
    { key: 'team', label: 'Команда' },
    { key: 'roles', label: 'Роли и доступ' },
    { key: 'schedule', label: 'Графики' },
    { key: 'payroll', label: 'Зарплаты' },
    { key: 'kpi', label: 'KPI' },
    { key: 'audit', label: 'Журнал' },
];

const showAddEmployee = ref(false);
const showEmployeeDetail = ref(false);
const selectedEmployee = ref(null);

const employees = [
    { id: 1, name: 'Алексей Козлов', position: 'Курьер', type: 'full_time', salary: 65000, rating: 4.9, isOnline: true, kpi: 95, avatar: '🧑‍💼' },
    { id: 2, name: 'Мария Иванова', position: 'Мастер-стилист', type: 'full_time', salary: 85000, rating: 4.8, isOnline: true, kpi: 92, avatar: '👩‍🎨' },
    { id: 3, name: 'Дмитрий Волков', position: 'Курьер', type: 'part_time', salary: 35000, rating: 4.7, isOnline: false, kpi: 88, avatar: '🧑‍💻' },
    { id: 4, name: 'Анна Петрова', position: 'Менеджер', type: 'full_time', salary: 95000, rating: 4.9, isOnline: true, kpi: 97, avatar: '👩‍💼' },
    { id: 5, name: 'Сергей Носов', position: 'Складской работник', type: 'contract', salary: 45000, rating: 4.5, isOnline: false, kpi: 82, avatar: '🧑‍🔧' },
];

const typeLabels = { full_time: 'Полная', part_time: 'Частичная', contract: 'Контракт', freelance: 'Фриланс' };
const typeColors = { full_time: 'success', part_time: 'info', contract: 'warning', freelance: 'neutral' };

function openEmployee(employee) {
    selectedEmployee.value = employee;
    showEmployeeDetail.value = true;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl lg:text-2xl font-bold text-(--t-text)">👥 Персонал</h1>
                <p class="text-xs text-(--t-text-3)">Команда, графики, KPI и зарплаты</p>
            </div>
            <VButton variant="primary" size="sm" @click="showAddEmployee = true">➕ Добавить сотрудника</VButton>
        </div>

        <!-- Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <VStatCard title="Всего сотрудников" value="5" icon="👥" color="primary" clickable />
            <VStatCard title="Онлайн сейчас" value="3" icon="🟢" color="emerald" clickable />
            <VStatCard title="Средний KPI" value="90.8%" icon="📊" :trend="3.4" color="indigo" clickable />
            <VStatCard title="ФОТ месяц" value="325k ₽" icon="💰" color="amber" clickable />
        </div>

        <!-- Tabs -->
        <VTabs :tabs="tabs" v-model="activeTab" variant="underline" />

        <!-- Team -->
        <template v-if="activeTab === 'team'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="emp in employees" :key="emp.id"
                     class="p-4 rounded-xl border border-(--t-border) hover:border-(--t-primary)/20 bg-(--t-surface) transition-all cursor-pointer hover:shadow-lg active:scale-[0.98]"
                     @click="openEmployee(emp)"
                >
                    <div class="flex items-center gap-3 mb-3">
                        <div class="relative">
                            <div class="w-12 h-12 rounded-full bg-linear-to-br from-(--t-primary-dim) to-(--t-card-hover) flex items-center justify-center text-2xl">
                                {{ emp.avatar }}
                            </div>
                            <div v-if="emp.isOnline" class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full bg-emerald-400 border-2 border-(--t-surface) animate-pulse" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-(--t-text) truncate">{{ emp.name }}</div>
                            <div class="text-xs text-(--t-text-3)">{{ emp.position }}</div>
                        </div>
                        <VBadge :text="typeLabels[emp.type]" :variant="typeColors[emp.type]" size="xs" />
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div class="p-2 rounded-lg bg-(--t-card-hover) text-center">
                            <div class="text-xs font-bold text-amber-400">★ {{ emp.rating }}</div>
                            <div class="text-[9px] text-(--t-text-3)">Рейтинг</div>
                        </div>
                        <div class="p-2 rounded-lg bg-(--t-card-hover) text-center">
                            <div class="text-xs font-bold" :class="emp.kpi >= 90 ? 'text-emerald-400' : emp.kpi >= 70 ? 'text-amber-400' : 'text-rose-400'">
                                {{ emp.kpi }}%
                            </div>
                            <div class="text-[9px] text-(--t-text-3)">KPI</div>
                        </div>
                        <div class="p-2 rounded-lg bg-(--t-card-hover) text-center">
                            <div class="text-xs font-bold text-(--t-text)">{{ (emp.salary/1000).toFixed(0) }}k</div>
                            <div class="text-[9px] text-(--t-text-3)">₽/мес</div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Schedule -->
        <template v-if="activeTab === 'schedule'">
            <VCard title="📅 Графики работы" subtitle="Расписание смен на текущую неделю">
                <div class="overflow-x-auto">
                    <div class="grid grid-cols-8 gap-1 min-w-[600px]">
                        <!-- Header -->
                        <div class="p-2" />
                        <div v-for="day in ['Пн','Вт','Ср','Чт','Пт','Сб','Вс']" :key="day" class="p-2 text-center text-xs font-bold text-(--t-text-2)">{{ day }}</div>

                        <!-- Rows -->
                        <template v-for="emp in employees" :key="emp.id">
                            <div class="p-2 text-xs text-(--t-text) font-medium flex items-center gap-1">
                                <span class="text-sm">{{ emp.avatar }}</span>
                                <span class="truncate">{{ emp.name.split(' ')[1] }}</span>
                            </div>
                            <div v-for="d in 7" :key="d"
                                 :class="['p-1.5 rounded-lg text-center text-[10px] cursor-pointer transition-all active:scale-95',
                                          d <= 5 && emp.type !== 'part_time' ? 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20' :
                                          d === 6 && emp.type === 'part_time' ? 'bg-amber-500/10 text-amber-400' :
                                          'bg-(--t-card-hover) text-(--t-text-3)']"
                            >
                                {{ d <= 5 && emp.type !== 'part_time' ? '9-18' : d === 6 && emp.type === 'part_time' ? '10-16' : 'Вых' }}
                            </div>
                        </template>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- Payroll -->
        <template v-if="activeTab === 'payroll'">
            <VCard title="💰 Зарплатная ведомость" subtitle="Апрель 2026">
                <div class="space-y-2">
                    <div v-for="emp in employees" :key="emp.id"
                         class="flex items-center gap-3 p-3 rounded-xl hover:bg-(--t-card-hover) transition-colors cursor-pointer active:scale-[0.99]"
                    >
                        <span class="text-lg">{{ emp.avatar }}</span>
                        <div class="flex-1">
                            <div class="text-sm text-(--t-text)">{{ emp.name }}</div>
                            <div class="text-[10px] text-(--t-text-3)">{{ emp.position }} • {{ typeLabels[emp.type] }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-(--t-text)">{{ Number(emp.salary).toLocaleString('ru') }} ₽</div>
                            <VBadge text="Выплачено" variant="success" size="xs" />
                        </div>
                    </div>
                </div>
                <template #footer>
                    <div class="flex items-center justify-between w-full">
                        <span class="text-sm text-(--t-text-3)">Итого ФОТ:</span>
                        <span class="text-lg font-bold text-(--t-text)">{{ Number(employees.reduce((s,e) => s+e.salary, 0)).toLocaleString('ru') }} ₽</span>
                    </div>
                </template>
            </VCard>
        </template>

        <!-- KPI -->
        <template v-if="activeTab === 'kpi'">
            <VCard title="📊 KPI сотрудников">
                <div class="space-y-3">
                    <div v-for="emp in [...employees].sort((a,b) => b.kpi - a.kpi)" :key="emp.id"
                         class="flex items-center gap-3 p-3 rounded-xl hover:bg-(--t-card-hover) transition-colors cursor-pointer"
                    >
                        <span class="text-lg">{{ emp.avatar }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-(--t-text)">{{ emp.name }}</span>
                                <span class="text-sm font-bold" :class="emp.kpi >= 90 ? 'text-emerald-400' : emp.kpi >= 70 ? 'text-amber-400' : 'text-rose-400'">
                                    {{ emp.kpi }}%
                                </span>
                            </div>
                            <div class="h-2 rounded-full bg-(--t-border) overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700"
                                     :class="emp.kpi >= 90 ? 'bg-linear-to-r from-emerald-500 to-emerald-300' : emp.kpi >= 70 ? 'bg-linear-to-r from-amber-500 to-amber-300' : 'bg-linear-to-r from-rose-500 to-rose-300'"
                                     :style="{width: emp.kpi + '%'}"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- Roles & Access -->
        <template v-if="activeTab === 'roles'">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <VCard title="🔐 Роли">
                    <div class="space-y-2">
                        <div v-for="role in [
                            { name: 'Администратор', icon: '👑', users: 1, permissions: ['all'], color: 'amber' },
                            { name: 'Менеджер', icon: '👩‍💼', users: 2, permissions: ['orders','products','analytics','employees'], color: 'indigo' },
                            { name: 'Курьер', icon: '🚴', users: 2, permissions: ['delivery','orders.view'], color: 'emerald' },
                            { name: 'Мастер', icon: '💇', users: 3, permissions: ['appointments','services','ai_constructor'], color: 'violet' },
                            { name: 'Складской', icon: '📦', users: 1, permissions: ['inventory','stock'], color: 'sky' },
                        ]" :key="role.name"
                           class="flex items-center gap-3 p-3 rounded-xl border border-(--t-border) hover:border-(--t-primary)/20 transition-all cursor-pointer active:scale-[0.99]"
                        >
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg" :class="`bg-${role.color}-500/10`">{{ role.icon }}</div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-(--t-text)">{{ role.name }}</div>
                                <div class="text-[10px] text-(--t-text-3)">{{ role.users }} чел. • {{ role.permissions.length }} прав</div>
                            </div>
                            <div class="flex flex-wrap gap-1 max-w-[120px]">
                                <span v-for="p in role.permissions.slice(0,2)" :key="p" class="px-1.5 py-0.5 text-[9px] rounded bg-(--t-card-hover) text-(--t-text-3)">{{ p }}</span>
                                <span v-if="role.permissions.length > 2" class="text-[9px] text-(--t-text-3)">+{{ role.permissions.length - 2 }}</span>
                            </div>
                        </div>
                    </div>
                    <template #footer>
                        <VButton variant="ghost" size="sm" full-width>+ Создать роль</VButton>
                    </template>
                </VCard>

                <VCard title="🏢 Доступ по вертикалям">
                    <div class="space-y-3">
                        <div v-for="v in [
                            { name: 'Beauty', icon: '💄', roles: ['Администратор','Менеджер','Мастер'] },
                            { name: 'Furniture', icon: '🛋️', roles: ['Администратор','Менеджер','Складской'] },
                            { name: 'Food', icon: '🍔', roles: ['Администратор','Менеджер','Курьер'] },
                            { name: 'Fashion', icon: '👗', roles: ['Администратор','Менеджер'] },
                        ]" :key="v.name"
                           class="p-3 rounded-xl bg-(--t-card-hover)"
                        >
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">{{ v.icon }}</span>
                                <span class="text-sm font-medium text-(--t-text)">{{ v.name }}</span>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <VBadge v-for="r in v.roles" :key="r" :text="r" variant="info" size="xs" />
                            </div>
                        </div>
                    </div>
                </VCard>
            </div>
        </template>

        <!-- Audit Log -->
        <template v-if="activeTab === 'audit'">
            <VCard title="📋 Журнал действий сотрудников">
                <div class="space-y-2">
                    <div v-for="log in [
                        { time: '14:35', user: 'Анна Петрова', action: 'Обновила статус заказа ORD-20492', icon: '📦', type: 'order' },
                        { time: '14:20', user: 'Алексей Козлов', action: 'Завершил доставку DLV-1847', icon: '🚴', type: 'delivery' },
                        { time: '13:55', user: 'Мария Иванова', action: 'Запустила AI-конструктор Beauty', icon: '🤖', type: 'ai' },
                        { time: '13:40', user: 'Анна Петрова', action: 'Создала промокод SPRING2026', icon: '🎫', type: 'marketing' },
                        { time: '12:15', user: 'Дмитрий Волков', action: 'Принял заказ на доставку', icon: '📥', type: 'order' },
                        { time: '11:30', user: 'Сергей Носов', action: 'Провёл инвентаризацию склада #3', icon: '📋', type: 'inventory' },
                        { time: '10:00', user: 'Система', action: 'Начислена зарплата (5 сотрудников)', icon: '💰', type: 'payroll' },
                        { time: '09:15', user: 'Анна Петрова', action: 'Изменила роль «Мастер» — добавлен доступ к AI', icon: '🔐', type: 'access' },
                    ]" :key="log.time + log.user"
                       class="flex items-start gap-3 p-3 rounded-xl hover:bg-(--t-card-hover) transition-colors"
                    >
                        <div class="w-8 h-8 rounded-lg bg-(--t-primary-dim) flex items-center justify-center text-sm shrink-0 mt-0.5">{{ log.icon }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs text-(--t-text)">
                                <span class="font-medium text-(--t-primary)">{{ log.user }}</span>
                                <span class="text-(--t-text-2)"> {{ log.action }}</span>
                            </div>
                            <div class="text-[10px] text-(--t-text-3) mt-0.5">Сегодня, {{ log.time }}</div>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <VButton variant="ghost" size="sm" full-width>Показать полный журнал →</VButton>
                </template>
            </VCard>
        </template>

        <!-- Add Employee Modal -->
        <VModal v-model="showAddEmployee" title="Добавить сотрудника" size="md">
            <div class="space-y-4">
                <VInput label="ФИО" placeholder="Иванов Иван Иванович" required />
                <VInput label="Должность" placeholder="Курьер, мастер, менеджер..." required />
                <div class="grid grid-cols-2 gap-3">
                    <VInput label="Оклад (₽)" type="number" placeholder="0" required />
                    <VInput label="Email" type="email" placeholder="ivanov@mail.ru" />
                </div>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showAddEmployee = false">Отмена</VButton>
                <VButton variant="primary">Добавить</VButton>
            </template>
        </VModal>

        <!-- Employee Detail Modal -->
        <VModal v-model="showEmployeeDetail" :title="selectedEmployee?.name" size="md">
            <template v-if="selectedEmployee">
                <div class="space-y-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-(--t-card-hover) flex items-center justify-center text-3xl">{{ selectedEmployee.avatar }}</div>
                        <div>
                            <div class="text-lg font-bold text-(--t-text)">{{ selectedEmployee.name }}</div>
                            <div class="text-sm text-(--t-text-3)">{{ selectedEmployee.position }}</div>
                            <VBadge :text="typeLabels[selectedEmployee.type]" :variant="typeColors[selectedEmployee.type]" size="sm" />
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="p-3 rounded-xl bg-(--t-card-hover) text-center">
                            <div class="text-[10px] text-(--t-text-3)">Рейтинг</div>
                            <div class="text-lg font-bold text-amber-400">★ {{ selectedEmployee.rating }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-(--t-card-hover) text-center">
                            <div class="text-[10px] text-(--t-text-3)">KPI</div>
                            <div class="text-lg font-bold" :class="selectedEmployee.kpi >= 90 ? 'text-emerald-400' : 'text-amber-400'">{{ selectedEmployee.kpi }}%</div>
                        </div>
                        <div class="p-3 rounded-xl bg-(--t-card-hover) text-center">
                            <div class="text-[10px] text-(--t-text-3)">Оклад</div>
                            <div class="text-lg font-bold text-(--t-text)">{{ (selectedEmployee.salary/1000).toFixed(0) }}k</div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <VButton variant="secondary" @click="showEmployeeDetail = false">Закрыть</VButton>
                <VButton variant="ghost">✏️ Редактировать</VButton>
                <VButton variant="primary">💰 Начислить бонус</VButton>
            </template>
        </VModal>
    </div>
</template>
