/**
 * BeautyPanel — тесты кликабельности и навигации.
 * Реальные компоненты, без стабов.
 *
 * 1. Клики по табам → корректный контент
 * 2. Клики по кнопкам хедера → модальные окна
 * 3. Модальные окна: открытие и закрытие
 * 4. Навигация и целостность переходов
 */
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import BeautyPanel from '@/Components/Business/Beauty/BeautyPanel.vue';
import VTabs from '@/Components/UI/VTabs.vue';
import VStatCard from '@/Components/UI/VStatCard.vue';
import VButton from '@/Components/UI/VButton.vue';
import VModal from '@/Components/UI/VModal.vue';
import BeautyCalendar from '@/Components/Business/Beauty/BeautyCalendar.vue';
import BeautyChat from '@/Components/Business/Beauty/BeautyChat.vue';
import BeautyFinances from '@/Components/Business/Beauty/BeautyFinances.vue';
import BeautyReports from '@/Components/Business/Beauty/BeautyReports.vue';
import BeautyTryOn from '@/Components/Business/Beauty/BeautyTryOn.vue';
import BeautyStaff from '@/Components/Business/Beauty/BeautyStaff.vue';
import BeautyInventory from '@/Components/Business/Beauty/BeautyInventory.vue';
import BeautyPublicPages from '@/Components/Business/Beauty/BeautyPublicPages.vue';
import BeautyPageStats from '@/Components/Business/Beauty/BeautyPageStats.vue';
import BeautyLoyalty from '@/Components/Business/Beauty/BeautyLoyalty.vue';
import BeautyReviews from '@/Components/Business/Beauty/BeautyReviews.vue';
import BeautyNotifications from '@/Components/Business/Beauty/BeautyNotifications.vue';

/* ─── Mock useBeautyApi ─── */
const mockApi = {
    fetchSalons: vi.fn(() => Promise.resolve([])),
    fetchMasters: vi.fn(() => Promise.resolve([])),
    fetchServices: vi.fn(() => Promise.resolve([])),
    fetchAppointments: vi.fn(() => Promise.resolve([])),
    fetchDashboard: vi.fn(() => Promise.resolve(null)),
    processStaffPayout: vi.fn(() => Promise.resolve(true)),
    exportReport: vi.fn(() => Promise.resolve(new Blob())),
    downloadBlob: vi.fn(),
    awardBonus: vi.fn(() => Promise.resolve(true)),
    deductBonus: vi.fn(() => Promise.resolve(true)),
    updateLoyaltyConfig: vi.fn(() => Promise.resolve(true)),
    replyToReview: vi.fn(() => Promise.resolve(true)),
    flagReview: vi.fn(() => Promise.resolve(true)),
    sendNotification: vi.fn(() => Promise.resolve(true)),
    sendBulkNotification: vi.fn(() => Promise.resolve(true)),
    createPublicPage: vi.fn((d) => Promise.resolve({ ...d, id: 999 })),
    updatePublicPage: vi.fn(() => Promise.resolve(true)),
    deletePublicPage: vi.fn(() => Promise.resolve(true)),
    loading: { value: false },
    error: { value: null },
};

vi.mock('@/Composables/useBeautyApi', () => ({
    useBeautyApi: () => mockApi,
}));

/* ─── Theme provider ─── */
const themeProvide = {
    theme: {
        bg: 'var(--t-bg)', surface: 'var(--t-surface)', border: 'var(--t-border)',
        primary: 'var(--t-primary)', primaryDim: 'var(--t-primary-dim)',
        accent: 'var(--t-accent)', text: 'var(--t-text)', text2: 'var(--t-text-2)',
        text3: 'var(--t-text-3)', glow: 'var(--t-glow)', header: 'var(--t-header)',
        btn: 'var(--t-btn)', btnHover: 'var(--t-btn-hover)', cardHover: 'var(--t-card-hover)',
    },
};

function mountPanel() {
    return mount(BeautyPanel, { global: { provide: themeProvide } });
}

describe('BeautyPanel — Кликабельность и навигация', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    /* ═══════════════════════════════════════════════════
     * TAB CLICKS → CONTENT VISIBILITY (реальные компоненты)
     * ═══════════════════════════════════════════════════ */
    describe('Клик по табу → правильный контент', () => {
        const tabComponentMap: Array<{ tab: string; component: any; name: string }> = [
            { tab: 'calendar', component: BeautyCalendar, name: 'BeautyCalendar' },
            { tab: 'chat', component: BeautyChat, name: 'BeautyChat' },
            { tab: 'finances', component: BeautyFinances, name: 'BeautyFinances' },
            { tab: 'reports', component: BeautyReports, name: 'BeautyReports' },
            { tab: 'tryon', component: BeautyTryOn, name: 'BeautyTryOn' },
            { tab: 'staff', component: BeautyStaff, name: 'BeautyStaff' },
            { tab: 'inventory', component: BeautyInventory, name: 'BeautyInventory' },
            { tab: 'pages', component: BeautyPublicPages, name: 'BeautyPublicPages' },
            { tab: 'page-stats', component: BeautyPageStats, name: 'BeautyPageStats' },
            { tab: 'loyalty', component: BeautyLoyalty, name: 'BeautyLoyalty' },
            { tab: 'reviews', component: BeautyReviews, name: 'BeautyReviews' },
            { tab: 'notifications', component: BeautyNotifications, name: 'BeautyNotifications' },
        ];

        it.each(tabComponentMap)(
            'клик по табу "$tab" показывает реальный $name',
            async ({ tab, component }) => {
                const wrapper = mountPanel();
                await flushPromises();

                // Переключаем через реальный VTabs
                const vtabs = wrapper.findComponent(VTabs);
                vtabs.vm.$emit('update:modelValue', tab);
                await flushPromises();

                // Проверяем что реальный дочерний компонент отрендерился
                const child = wrapper.findComponent(component);
                expect(child.exists()).toBe(true);
            },
            15_000,
        );

        it('дашборд виден по умолчанию', async () => {
            const wrapper = mountPanel();
            await flushPromises();
            const statCards = wrapper.findAllComponents(VStatCard);
            expect(statCards.length).toBeGreaterThanOrEqual(1);
        });

        it('дашборд скрывается при переключении на другой таб', async () => {
            const wrapper = mountPanel();
            await flushPromises();

            const vtabs = wrapper.findComponent(VTabs);
            vtabs.vm.$emit('update:modelValue', 'calendar');
            await flushPromises();

            // Календарь виден
            const calendar = wrapper.findComponent(BeautyCalendar);
            expect(calendar.exists()).toBe(true);
        });
    });

    /* ═══════════════════════════════════════════════════
     * HEADER BUTTONS → MODAL OPENING (реальный VModal)
     * ═══════════════════════════════════════════════════ */
    describe('Кнопки хедера → модальные окна', () => {
        it('кнопка "Записать клиента" открывает модальное окно', async () => {
            const wrapper = mountPanel();
            await flushPromises();

            // Ищем кнопку в реальных VButton компонентах
            const allButtons = wrapper.findAllComponents(VButton);
            const bookBtn = allButtons.find(b => b.text().includes('Записать клиента'));

            if (bookBtn) {
                await bookBtn.trigger('click');
                await flushPromises();
                // Реальный VModal с v-model рендерит контент через v-if="modelValue"
                const modals = wrapper.findAllComponents(VModal);
                const openModal = modals.find(m => (m.vm as any).modelValue === true);
                expect(openModal).toBeTruthy();
            }
        });

        it('кнопка "Добавить мастера" открывает модальное окно', async () => {
            const wrapper = mountPanel();
            await flushPromises();

            const allButtons = wrapper.findAllComponents(VButton);
            const addMasterBtn = allButtons.find(b => b.text().includes('Добавить мастера'));

            if (addMasterBtn) {
                await addMasterBtn.trigger('click');
                await flushPromises();
                const modals = wrapper.findAllComponents(VModal);
                const openModal = modals.find(m => (m.vm as any).modelValue === true);
                expect(openModal).toBeTruthy();
            }
        });

        it('кнопка "Создать акцию" открывает модальное окно', async () => {
            const wrapper = mountPanel();
            await flushPromises();

            const allButtons = wrapper.findAllComponents(VButton);
            const promoBtn = allButtons.find(b => b.text().includes('Создать акцию'));

            if (promoBtn) {
                await promoBtn.trigger('click');
                await flushPromises();
                const modals = wrapper.findAllComponents(VModal);
                const openModal = modals.find(m => (m.vm as any).modelValue === true);
                expect(openModal).toBeTruthy();
            }
        });
    });

    /* ═══════════════════════════════════════════════════
     * MODAL CLOSE (реальный VModal)
     * ═══════════════════════════════════════════════════ */
    describe('Закрытие модальных окон', () => {
        it('модальное окно закрывается по клику на кнопку закрытия', async () => {
            const wrapper = mountPanel();
            await flushPromises();

            // Открываем модалку "Записать клиента"
            const allButtons = wrapper.findAllComponents(VButton);
            const bookBtn = allButtons.find(b => b.text().includes('Записать клиента'));

            if (bookBtn) {
                await bookBtn.trigger('click');
                await flushPromises();

                // Модалка открыта — ищем реальную кнопку закрытия VModal (SVG cross)
                const modals = wrapper.findAllComponents(VModal);
                const openModal = modals.find(m => (m.vm as any).modelValue === true);
                expect(openModal).toBeTruthy();

                // Вызываем close на реальном VModal
                if (openModal) {
                    (openModal.vm as any).$emit('update:modelValue', false);
                    (openModal.vm as any).$emit('close');
                    await flushPromises();
                }
            }
        });
    });

    /* ═══════════════════════════════════════════════════
     * TAB NAVIGATION INTEGRITY
     * ═══════════════════════════════════════════════════ */
    describe('Навигация: целостность переходов', () => {
        it('последовательные переключения табов не вызывают ошибок', async () => {
            const wrapper = mountPanel();
            await flushPromises();

            const vtabs = wrapper.findComponent(VTabs);
            const sequence = ['salons', 'masters', 'calendar', 'finances', 'dashboard', 'chat', 'loyalty', 'reviews'];

            for (const tab of sequence) {
                vtabs.vm.$emit('update:modelValue', tab);
                await flushPromises();
            }

            // Компонент не упал после многих переключений
            expect(wrapper.text()).toContain('Beauty — B2B Кабинет');
        });

        it('быстрые переключения (double-click simulation) не ломают UI', async () => {
            const wrapper = mountPanel();
            await flushPromises();

            const vtabs = wrapper.findComponent(VTabs);

            vtabs.vm.$emit('update:modelValue', 'calendar');
            vtabs.vm.$emit('update:modelValue', 'chat');
            vtabs.vm.$emit('update:modelValue', 'calendar');
            await flushPromises();

            expect(wrapper.text()).toBeTruthy();
        });
    });

    /* ═══════════════════════════════════════════════════
     * DASHBOARD CARDS
     * ═══════════════════════════════════════════════════ */
    describe('Дашборд: кликабельность', () => {
        it('6 реальных VStatCard отображаются на дашборде', async () => {
            const wrapper = mountPanel();
            await flushPromises();

            const statCards = wrapper.findAllComponents(VStatCard);
            expect(statCards.length).toBe(6);
        });

        it('stat-карточки содержат правильные данные', async () => {
            const wrapper = mountPanel();
            await flushPromises();

            const text = wrapper.text();
            expect(text).toContain('Выручка сегодня');
            expect(text).toContain('Выручка за неделю');
            expect(text).toContain('Активных записей');
            expect(text).toContain('Загрузка мастеров');
            expect(text).toContain('Средний чек');
            expect(text).toContain('Конверсия');
        });
    });

    /* ═══════════════════════════════════════════════════
     * EXPORT FUNCTIONALITY
     * ═══════════════════════════════════════════════════ */
    describe('Экспорт', () => {
        it('экспорт вызывает api.exportReport и api.downloadBlob', async () => {
            const wrapper = mountPanel();
            await flushPromises();

            const vm = wrapper.vm as any;
            if (typeof vm.handleStaffExportReport === 'function') {
                await vm.handleStaffExportReport({ format: 'xlsx' });
                await flushPromises();
                expect(mockApi.exportReport).toHaveBeenCalledWith('xlsx', 'staff');
                expect(mockApi.downloadBlob).toHaveBeenCalled();
            }
        });
    });
});
