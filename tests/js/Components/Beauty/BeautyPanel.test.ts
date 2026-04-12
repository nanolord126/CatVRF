/**
 * BeautyPanel.vue — основные тесты.
 * Реальные компоненты, без стабов.
 *
 * 1. Рендеринг компонента
 * 2. Переключение 20 табов
 * 3. Загрузка данных с API (мок)
 */
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import BeautyPanel from '@/Components/Business/Beauty/BeautyPanel.vue';
import VTabs from '@/Components/UI/VTabs.vue';
import VStatCard from '@/Components/UI/VStatCard.vue';
import VButton from '@/Components/UI/VButton.vue';

/* ─── Mock useBeautyApi composable ─── */
const mockApi = {
    fetchSalons: vi.fn(() => Promise.resolve([])),
    fetchMasters: vi.fn(() => Promise.resolve([])),
    fetchServices: vi.fn(() => Promise.resolve([])),
    fetchAppointments: vi.fn(() => Promise.resolve([])),
    fetchDashboard: vi.fn(() => Promise.resolve(null)),
    fetchReviews: vi.fn(() => Promise.resolve([])),
    fetchProducts: vi.fn(() => Promise.resolve([])),
    fetchConsumables: vi.fn(() => Promise.resolve([])),
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
    createPublicPage: vi.fn((data) => Promise.resolve({ ...data, id: 999 })),
    updatePublicPage: vi.fn(() => Promise.resolve(true)),
    deletePublicPage: vi.fn(() => Promise.resolve(true)),
    loading: { value: false },
    error: { value: null },
};

vi.mock('@/Composables/useBeautyApi', () => ({
    useBeautyApi: () => mockApi,
}));

/* ─── Theme provider for child components that inject('theme') ─── */
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
    return mount(BeautyPanel, {
        global: {
            provide: themeProvide,
        },
    });
}

describe('BeautyPanel.vue', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    /* ═══════════════════════════════════════════════════
     * RENDERING
     * ═══════════════════════════════════════════════════ */
    describe('Рендеринг', () => {
        it('рендерит заголовок "Beauty — B2B Кабинет"', async () => {
            const wrapper = mountPanel();
            await flushPromises();
            expect(wrapper.text()).toContain('Beauty — B2B Кабинет');
        });

        it('рендерит реальный VTabs с 20 табами', async () => {
            const wrapper = mountPanel();
            await flushPromises();
            const vtabs = wrapper.findComponent(VTabs);
            expect(vtabs.exists()).toBe(true);
            const tabButtons = vtabs.findAll('button');
            expect(tabButtons.length).toBe(20);
        });

        it('рендерит 6 реальных VStatCard на дашборде', async () => {
            const wrapper = mountPanel();
            await flushPromises();
            const statCards = wrapper.findAllComponents(VStatCard);
            expect(statCards.length).toBeGreaterThanOrEqual(6);
        });

        it('рендерит минимум 3 кнопки VButton в хедере', async () => {
            const wrapper = mountPanel();
            await flushPromises();
            const allButtons = wrapper.findAllComponents(VButton);
            expect(allButtons.length).toBeGreaterThanOrEqual(3);
        });
    });

    /* ═══════════════════════════════════════════════════
     * API LOADING ON MOUNT
     * ═══════════════════════════════════════════════════ */
    describe('Загрузка данных с API', () => {
        it('вызывает API endpoints при монтировании', async () => {
            mountPanel();
            await flushPromises();
            expect(mockApi.fetchSalons).toHaveBeenCalledTimes(1);
            expect(mockApi.fetchMasters).toHaveBeenCalledTimes(1);
            expect(mockApi.fetchServices).toHaveBeenCalledTimes(1);
            expect(mockApi.fetchAppointments).toHaveBeenCalledTimes(1);
            expect(mockApi.fetchDashboard).toHaveBeenCalledTimes(1);
        });

        it('обновляет dashStats когда API возвращает данные', async () => {
            mockApi.fetchDashboard.mockResolvedValueOnce({
                revenue_today: 250000,
                revenue_week: 1500000,
                active_bookings: 42,
                masters_load: 87,
                avg_check: 3200,
                conversion: 65,
            });
            const wrapper = mountPanel();
            await flushPromises();
            const text = wrapper.text();
            expect(text).toContain('250\u00a0000');
        });

        it('продолжает работать при ошибке API (fallback на мок)', async () => {
            mockApi.fetchSalons.mockRejectedValueOnce(new Error('Network Error'));
            mockApi.fetchMasters.mockRejectedValueOnce(new Error('Network Error'));
            mockApi.fetchServices.mockRejectedValueOnce(new Error('Network Error'));
            mockApi.fetchAppointments.mockRejectedValueOnce(new Error('Network Error'));
            mockApi.fetchDashboard.mockRejectedValueOnce(new Error('Network Error'));
            const wrapper = mountPanel();
            await flushPromises();
            expect(wrapper.text()).toContain('Beauty — B2B Кабинет');
        });
    });

    /* ═══════════════════════════════════════════════════
     * TAB SWITCHING (все 20 табов через реальный VTabs)
     * ═══════════════════════════════════════════════════ */
    describe('Переключение табов', () => {
        const tabKeys = [
            'dashboard', 'salons', 'masters', 'staff', 'inventory',
            'services', 'calendar', 'bookings', 'clients', 'chat',
            'finances', 'promo', 'reports', 'tryon', 'pages',
            'page-stats', 'loyalty', 'reviews', 'notifications', 'config',
        ];

        it.each(tabKeys)('переключение на таб "%s" работает', async (tabKey) => {
            const wrapper = mountPanel();
            await flushPromises();

            // Переключаем через реальный VTabs
            const vtabs = wrapper.findComponent(VTabs);
            expect(vtabs.exists()).toBe(true);
            vtabs.vm.$emit('update:modelValue', tabKey);
            await flushPromises();

            // Компонент не упал после переключения
            expect(wrapper.text()).toBeTruthy();
        });
    });
});
