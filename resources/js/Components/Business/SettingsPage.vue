<script setup>
/**
 * SettingsPage — настройки бизнеса, профиль, интеграции, безопасность, тарифы.
 */
import { ref } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VTabs from '../UI/VTabs.vue';
import VInput from '../UI/VInput.vue';
import VModal from '../UI/VModal.vue';

const activeTab = ref('profile');
const tabs = [
    { key: 'profile', label: 'Профиль' },
    { key: 'integrations', label: 'Интеграции' },
    { key: 'security', label: 'Безопасность' },
    { key: 'billing', label: 'Тарифы' },
    { key: 'notifications', label: 'Уведомления' },
    { key: 'danger', label: '⚠️ Опасная зона' },
];

const showChange2FA = ref(false);
const show2FASetup = ref(false);
const showDeactivate = ref(false);
const twoFAEnabled = ref(true);
const darkMode = ref(true);
const deactivateConfirm = ref('');

const loginHistory = ref([
    { date: '2026-04-08 14:32', device: 'Chrome Windows', ip: '192.168.1.42', location: 'Москва', status: 'success' },
    { date: '2026-04-08 10:15', device: 'Safari iPhone', ip: '10.0.0.18', location: 'Москва', status: 'success' },
    { date: '2026-04-07 23:40', device: 'Firefox Linux', ip: '85.93.12.44', location: 'Санкт-Петербург', status: 'failed' },
    { date: '2026-04-07 18:20', device: 'Chrome macOS', ip: '172.16.0.5', location: 'Москва', status: 'success' },
    { date: '2026-04-06 09:00', device: 'Edge Windows', ip: '192.168.1.42', location: 'Москва', status: 'success' },
    { date: '2026-04-05 14:10', device: 'Chrome Android', ip: '95.24.8.11', location: 'Казань', status: 'failed' },
]);

const securityAudit = ref([
    { date: '2026-04-08 14:35', action: 'Изменены настройки уведомлений', icon: '🔔' },
    { date: '2026-04-07 12:00', action: 'Добавлен API-ключ для 1С', icon: '🔑' },
    { date: '2026-04-06 10:30', action: 'Обновлён пароль аккаунта', icon: '🔐' },
    { date: '2026-04-05 16:20', action: 'Подключена интеграция Telegram', icon: '📱' },
    { date: '2026-04-04 09:45', action: 'Включена 2FA аутентификация', icon: '🛡️' },
]);
const currentTheme = ref('mint');
const themes = [
    { key: 'mint', label: '🌿 Mint', color: '#22d3ee' },
    { key: 'day', label: '☀️ Day', color: '#3b82f6' },
    { key: 'night', label: '🌙 Night', color: '#8b5cf6' },
    { key: 'sunset', label: '🌅 Sunset', color: '#f59e0b' },
    { key: 'lavender', label: '💜 Lavender', color: '#a78bfa' },
];

const notifSettings = ref([
    { key: 'orders', label: 'Новые заказы', email: true, push: true, sms: false },
    { key: 'payments', label: 'Платежи и выплаты', email: true, push: true, sms: true },
    { key: 'fraud', label: 'Фрод-алерты', email: true, push: true, sms: true },
    { key: 'stock', label: 'Low stock', email: true, push: false, sms: false },
    { key: 'marketing', label: 'Маркетинг', email: false, push: true, sms: false },
    { key: 'ai', label: 'AI-конструкторы', email: false, push: true, sms: false },
]);
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-xl lg:text-2xl font-bold text-(--t-text)">⚙️ Настройки</h1>
            <p class="text-xs text-(--t-text-3)">Профиль, интеграции, безопасность и тарифы</p>
        </div>

        <!-- Tabs -->
        <VTabs :tabs="tabs" v-model="activeTab" variant="underline" />

        <!-- Profile -->
        <template v-if="activeTab === 'profile'">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <VCard title="🏢 Данные бизнеса">
                    <div class="space-y-4">
                        <VInput label="Название компании" model-value="CatVRF Beauty Studio" />
                        <VInput label="ИНН" model-value="7701234567" />
                        <VInput label="Юридический адрес" model-value="Москва, ул. Красная 15, оф. 301" />
                        <VInput label="Email" type="email" model-value="admin@catvrf-beauty.ru" />
                        <VInput label="Телефон" model-value="+7 (495) 123-45-67" />
                    </div>
                    <template #footer>
                        <VButton variant="primary" class="ml-auto">💾 Сохранить</VButton>
                    </template>
                </VCard>

                <VCard title="🎨 Оформление">
                    <div class="space-y-4">
                        <!-- Theme selector -->
                        <div>
                            <div class="text-xs text-(--t-text-2) mb-2">Тема оформления</div>
                            <div class="grid grid-cols-5 gap-2">
                                <button v-for="t in themes" :key="t.key"
                                        :class="['p-2 rounded-xl border-2 text-center cursor-pointer transition-all active:scale-95',
                                                 currentTheme === t.key ? 'border-(--t-primary) shadow-lg' : 'border-(--t-border) hover:border-(--t-text-3)']"
                                        @click="currentTheme = t.key"
                                >
                                    <div class="w-6 h-6 rounded-full mx-auto mb-1" :style="{background: t.color}" />
                                    <div class="text-[9px] text-(--t-text-3)">{{ t.label }}</div>
                                </button>
                            </div>
                        </div>

                        <!-- Logo -->
                        <div>
                            <div class="text-xs text-(--t-text-2) mb-2">Логотип</div>
                            <div class="w-20 h-20 rounded-xl bg-(--t-card-hover) border-2 border-dashed border-(--t-border) flex items-center justify-center cursor-pointer hover:border-(--t-primary) transition-colors active:scale-95">
                                <span class="text-3xl">🐱</span>
                            </div>
                        </div>

                        <!-- Vertical -->
                        <div>
                            <div class="text-xs text-(--t-text-2) mb-2">Активные вертикали</div>
                            <div class="flex flex-wrap gap-2">
                                <VBadge v-for="v in ['💄 Beauty', '🛋️ Furniture', '🍔 Food', '👗 Fashion']" :key="v" :text="v" variant="success" size="sm" removable />
                                <button class="px-3 py-1 rounded-full border border-dashed border-(--t-border) text-xs text-(--t-text-3) cursor-pointer hover:border-(--t-primary) hover:text-(--t-primary) transition-colors active:scale-95">
                                    + Добавить
                                </button>
                            </div>
                        </div>
                    </div>
                </VCard>
            </div>
        </template>

        <!-- Integrations -->
        <template v-if="activeTab === 'integrations'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="int_ in [
                    {name: '1C', desc: 'Синхронизация товаров и заказов', icon: '🏗️', connected: true},
                    {name: 'Telegram Bot', desc: 'Уведомления и управление', icon: '📱', connected: true},
                    {name: 'Yandex Maps', desc: 'Карты и маршруты', icon: '🗺️', connected: true},
                    {name: 'Tinkoff Pay', desc: 'Приём платежей', icon: '💳', connected: true},
                    {name: 'SMS.ru', desc: 'SMS-уведомления', icon: '📨', connected: false},
                    {name: 'Slack', desc: 'Командные уведомления', icon: '💬', connected: false},
                    {name: 'Google Analytics', desc: 'Аналитика трафика', icon: '📊', connected: false},
                    {name: 'OpenAI', desc: 'AI-конструкторы', icon: '🤖', connected: true},
                    {name: 'Firebase', desc: 'Push-уведомления', icon: '🔔', connected: true},
                ]" :key="int_.name"
                     :class="['p-4 rounded-xl border transition-all cursor-pointer hover:shadow-lg active:scale-[0.98]',
                              int_.connected ? 'border-emerald-500/20 bg-(--t-surface)' : 'border-(--t-border) bg-(--t-surface) opacity-70 hover:opacity-100']"
                >
                    <div class="flex items-start justify-between mb-2">
                        <span class="text-2xl">{{ int_.icon }}</span>
                        <VBadge :text="int_.connected ? 'Подключено' : 'Не подключено'" :variant="int_.connected ? 'success' : 'neutral'" size="xs" />
                    </div>
                    <div class="text-sm font-bold text-(--t-text) mb-1">{{ int_.name }}</div>
                    <div class="text-[10px] text-(--t-text-3) mb-3">{{ int_.desc }}</div>
                    <VButton :variant="int_.connected ? 'ghost' : 'primary'" size="xs" full-width>
                        {{ int_.connected ? '⚙️ Настроить' : '🔗 Подключить' }}
                    </VButton>
                </div>
            </div>
        </template>

        <!-- Security -->
        <template v-if="activeTab === 'security'">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <VCard title="🔐 Безопасность аккаунта">
                    <div class="space-y-3">
                        <!-- 2FA -->
                        <div class="flex items-center justify-between p-3 rounded-xl bg-(--t-card-hover)">
                            <div>
                                <div class="text-sm text-(--t-text)">Двухфакторная аутентификация</div>
                                <div class="text-[10px] text-(--t-text-3)">TOTP через приложение</div>
                            </div>
                            <button :class="['relative w-12 h-6 rounded-full transition-colors cursor-pointer active:scale-95',
                                            twoFAEnabled ? 'bg-emerald-500' : 'bg-(--t-border)']"
                                    @click="twoFAEnabled = !twoFAEnabled"
                            >
                                <div :class="['absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform',
                                             twoFAEnabled ? 'translate-x-6' : 'translate-x-0.5']" />
                            </button>
                        </div>

                        <!-- Password -->
                        <div class="flex items-center justify-between p-3 rounded-xl bg-(--t-card-hover) cursor-pointer hover:bg-(--t-border) transition-colors active:scale-[0.99]">
                            <div>
                                <div class="text-sm text-(--t-text)">Пароль</div>
                                <div class="text-[10px] text-(--t-text-3)">Последнее изменение: 15.03.2026</div>
                            </div>
                            <VButton variant="ghost" size="xs">Изменить</VButton>
                        </div>

                        <!-- Sessions -->
                        <div class="flex items-center justify-between p-3 rounded-xl bg-(--t-card-hover) cursor-pointer hover:bg-(--t-border) transition-colors active:scale-[0.99]">
                            <div>
                                <div class="text-sm text-(--t-text)">Активные сессии</div>
                                <div class="text-[10px] text-(--t-text-3)">3 устройства</div>
                            </div>
                            <VButton variant="danger" size="xs">Завершить все</VButton>
                        </div>
                    </div>
                </VCard>

                <VCard title="📱 Устройства">
                    <div class="space-y-2">
                        <div v-for="dev in [
                            {name: 'Chrome Windows', ip: '192.168.1.42', time: 'Сейчас', current: true},
                            {name: 'Safari iPhone 15', ip: '10.0.0.18', time: '2 часа назад', current: false},
                            {name: 'Firefox macOS', ip: '172.16.0.5', time: 'Вчера', current: false},
                        ]" :key="dev.name"
                           class="flex items-center justify-between p-3 rounded-xl hover:bg-(--t-card-hover) transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div :class="['w-2 h-2 rounded-full', dev.current ? 'bg-emerald-400 animate-pulse' : 'bg-(--t-text-3)']" />
                                <div>
                                    <div class="text-sm text-(--t-text)">{{ dev.name }}</div>
                                    <div class="text-[10px] text-(--t-text-3)">{{ dev.ip }} • {{ dev.time }}</div>
                                </div>
                            </div>
                            <button v-if="!dev.current" class="text-xs text-rose-400 cursor-pointer hover:underline active:scale-95">Отключить</button>
                            <VBadge v-else text="Текущее" variant="success" size="xs" />
                        </div>
                    </div>
                </VCard>

                <!-- Login History -->
                <VCard title="📋 История входов" class="lg:col-span-2">
                    <div class="space-y-1">
                        <div class="grid grid-cols-[1fr_1fr_120px_100px_80px] gap-2 p-2 text-[10px] text-(--t-text-3) font-bold uppercase">
                            <span>Дата</span><span>Устройство</span><span>IP</span><span>Город</span><span class="text-center">Статус</span>
                        </div>
                        <div v-for="entry in loginHistory" :key="entry.date"
                             class="grid grid-cols-[1fr_1fr_120px_100px_80px] gap-2 p-2 rounded-lg hover:bg-(--t-card-hover) transition-colors items-center"
                        >
                            <span class="text-xs text-(--t-text-2)">{{ entry.date }}</span>
                            <span class="text-xs text-(--t-text)">{{ entry.device }}</span>
                            <span class="text-xs font-mono text-(--t-text-3)">{{ entry.ip }}</span>
                            <span class="text-xs text-(--t-text-2)">{{ entry.location }}</span>
                            <div class="flex justify-center">
                                <VBadge :text="entry.status === 'success' ? '✅' : '❌'" :variant="entry.status === 'success' ? 'success' : 'danger'" size="xs" />
                            </div>
                        </div>
                    </div>
                </VCard>

                <!-- Security Audit -->
                <VCard title="🛡️ Аудит безопасности">
                    <div class="space-y-2">
                        <div v-for="log in securityAudit" :key="log.date"
                             class="flex items-start gap-3 p-2 rounded-lg hover:bg-(--t-card-hover) transition-colors"
                        >
                            <span class="text-sm">{{ log.icon }}</span>
                            <div>
                                <div class="text-xs text-(--t-text)">{{ log.action }}</div>
                                <div class="text-[10px] text-(--t-text-3)">{{ log.date }}</div>
                            </div>
                        </div>
                    </div>
                </VCard>

                <!-- 2FA Setup + Account Deactivation -->
                <VCard title="⚠️ Опасная зона">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-(--t-card-hover)">
                            <div>
                                <div class="text-sm text-(--t-text)">Настроить 2FA заново</div>
                                <div class="text-[10px] text-(--t-text-3)">Пересканировать QR-код</div>
                            </div>
                            <VButton variant="secondary" size="xs" @click="show2FASetup = true">🔄 Перенастроить</VButton>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-rose-500/5 border border-rose-500/10">
                            <div>
                                <div class="text-sm text-rose-400">Деактивировать аккаунт</div>
                                <div class="text-[10px] text-(--t-text-3)">Необратимое действие</div>
                            </div>
                            <VButton variant="danger" size="xs" @click="showDeactivate = true">🗑️ Деактивировать</VButton>
                        </div>
                    </div>
                </VCard>
            </div>
        </template>

        <!-- Billing -->
        <template v-if="activeTab === 'billing'">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div v-for="plan in [
                    {name: 'Start', price: '0', features: ['1 вертикаль', '100 заказов/мес', 'Базовая аналитика', 'Email поддержка'], current: false},
                    {name: 'Business', price: '4 990', features: ['5 вертикалей', 'Безлимит заказов', 'AI-конструкторы', 'Приоритет поддержка', 'B2B API'], current: true},
                    {name: 'Enterprise', price: '24 990', features: ['Все вертикали', 'Безлимит всего', 'ML-аналитика', 'Выделенный менеджер', 'SLA 99.9%', 'Custom AI'], current: false},
                ]" :key="plan.name"
                     :class="['p-5 rounded-xl border-2 transition-all cursor-pointer hover:shadow-xl active:scale-[0.98]',
                              plan.current ? 'border-(--t-primary) shadow-lg shadow-(--t-glow)' : 'border-(--t-border) hover:border-(--t-text-3)']"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-lg font-bold text-(--t-text)">{{ plan.name }}</div>
                        <VBadge v-if="plan.current" text="Текущий" variant="success" size="xs" />
                    </div>
                    <div class="mb-4">
                        <span class="text-2xl font-extrabold text-(--t-text)">{{ plan.price }}</span>
                        <span class="text-xs text-(--t-text-3)"> ₽/мес</span>
                    </div>
                    <ul class="space-y-2 mb-4">
                        <li v-for="f in plan.features" :key="f" class="flex items-center gap-2 text-xs text-(--t-text-2)">
                            <span class="text-emerald-400">✓</span>
                            {{ f }}
                        </li>
                    </ul>
                    <VButton :variant="plan.current ? 'secondary' : 'primary'" size="sm" full-width>
                        {{ plan.current ? 'Текущий план' : 'Выбрать' }}
                    </VButton>
                </div>
            </div>
        </template>

        <!-- Notifications Settings -->
        <template v-if="activeTab === 'notifications'">
            <VCard title="🔔 Настройки уведомлений" subtitle="Выберите каналы для каждого типа">
                <div class="space-y-1">
                    <!-- Header -->
                    <div class="grid grid-cols-[1fr_60px_60px_60px] gap-2 p-2 text-[10px] text-(--t-text-3) font-bold uppercase">
                        <span>Событие</span>
                        <span class="text-center">Email</span>
                        <span class="text-center">Push</span>
                        <span class="text-center">SMS</span>
                    </div>

                    <div v-for="ns in notifSettings" :key="ns.key"
                         class="grid grid-cols-[1fr_60px_60px_60px] gap-2 p-2.5 rounded-lg hover:bg-(--t-card-hover) transition-colors items-center"
                    >
                        <span class="text-sm text-(--t-text)">{{ ns.label }}</span>
                        <div class="flex justify-center">
                            <button :class="['w-8 h-5 rounded-full transition-colors cursor-pointer active:scale-90',
                                            ns.email ? 'bg-emerald-500' : 'bg-(--t-border)']"
                                    @click="ns.email = !ns.email"
                            >
                                <div :class="['w-3.5 h-3.5 rounded-full bg-white shadow transition-transform',
                                             ns.email ? 'translate-x-3.5' : 'translate-x-0.5']" />
                            </button>
                        </div>
                        <div class="flex justify-center">
                            <button :class="['w-8 h-5 rounded-full transition-colors cursor-pointer active:scale-90',
                                            ns.push ? 'bg-emerald-500' : 'bg-(--t-border)']"
                                    @click="ns.push = !ns.push"
                            >
                                <div :class="['w-3.5 h-3.5 rounded-full bg-white shadow transition-transform',
                                             ns.push ? 'translate-x-3.5' : 'translate-x-0.5']" />
                            </button>
                        </div>
                        <div class="flex justify-center">
                            <button :class="['w-8 h-5 rounded-full transition-colors cursor-pointer active:scale-90',
                                            ns.sms ? 'bg-emerald-500' : 'bg-(--t-border)']"
                                    @click="ns.sms = !ns.sms"
                            >
                                <div :class="['w-3.5 h-3.5 rounded-full bg-white shadow transition-transform',
                                             ns.sms ? 'translate-x-3.5' : 'translate-x-0.5']" />
                            </button>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <VButton variant="primary" class="ml-auto">💾 Сохранить настройки</VButton>
                </template>
            </VCard>
        </template>
        <!-- 2FA Setup Modal -->
        <VModal v-model="show2FASetup" title="Настройка 2FA" size="sm">
            <div class="space-y-4 text-center">
                <div class="w-48 h-48 mx-auto rounded-xl bg-white p-3 flex items-center justify-center">
                    <div class="w-full h-full bg-[repeating-conic-gradient(#000_0_25%,#fff_0_50%)] bg-[length:12px_12px] rounded-lg opacity-30" />
                </div>
                <div class="text-xs text-(--t-text-3)">Отсканируйте QR-код в приложении Google Authenticator</div>
                <div class="p-3 rounded-lg bg-(--t-card-hover)">
                    <div class="text-[10px] text-(--t-text-3) mb-1">Или введите ключ вручную:</div>
                    <div class="font-mono text-sm text-(--t-primary) select-all tracking-wider">JBSWY3DPEHPK3PXP</div>
                </div>
                <VInput label="Код из приложения" placeholder="000 000" />
                <div class="p-3 rounded-lg bg-amber-500/5 border border-amber-500/10 text-left">
                    <div class="text-xs font-bold text-amber-400 mb-1">Коды восстановления:</div>
                    <div class="grid grid-cols-2 gap-1 font-mono text-[10px] text-(--t-text-2)">
                        <span>a8f2-k9d1</span><span>m3n7-p4q2</span>
                        <span>r5s8-t6u3</span><span>v1w4-x7y9</span>
                        <span>z2a5-b8c1</span><span>d4e7-f0g3</span>
                    </div>
                </div>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="show2FASetup = false">Отмена</VButton>
                <VButton variant="primary">Подтвердить</VButton>
            </template>
        </VModal>

        <!-- Deactivate Account Modal -->
        <VModal v-model="showDeactivate" title="Деактивация аккаунта" size="sm">
            <div class="space-y-4">
                <div class="p-4 rounded-xl bg-rose-500/5 border border-rose-500/10 text-center">
                    <div class="text-3xl mb-2">⚠️</div>
                    <div class="text-sm font-bold text-rose-400">Это действие необратимо!</div>
                    <div class="text-xs text-(--t-text-3) mt-1">Все данные, заказы и интеграции будут удалены через 30 дней.</div>
                </div>
                <VInput v-model="deactivateConfirm" label="Введите 'УДАЛИТЬ' для подтверждения" placeholder="УДАЛИТЬ" />
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showDeactivate = false">Отмена</VButton>
                <VButton variant="danger" :disabled="deactivateConfirm !== 'УДАЛИТЬ'">Деактивировать навсегда</VButton>
            </template>
        </VModal>
    </div>
</template>
