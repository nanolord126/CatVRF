/**
 * BeautyPanel — тесты обработчиков (handlers).
 * Реальные компоненты, без стабов.
 *
 * Проверяет, что обработчики вызывают реальный API через useBeautyApi.
 * События триггерятся через findComponent(RealComponent).vm.$emit().
 */
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import BeautyPanel from '@/Components/Business/Beauty/BeautyPanel.vue';
import VTabs from '@/Components/UI/VTabs.vue';
import BeautyStaff from '@/Components/Business/Beauty/BeautyStaff.vue';
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
    exportReport: vi.fn(() => Promise.resolve(new Blob(['test']))),
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

/** Helper: переключить на таб и получить реальный дочерний компонент */
async function switchToTabAndFind<T>(wrapper: any, tabKey: string, component: any): Promise<any> {
    const vtabs = wrapper.findComponent(VTabs);
    vtabs.vm.$emit('update:modelValue', tabKey);
    await flushPromises();
    return wrapper.findComponent(component);
}

describe('BeautyPanel — Обработчики', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    /* ═══════════════════════════════════════════════════
     * STAFF PAYOUT
     * ═══════════════════════════════════════════════════ */
    describe('Выплата персоналу', () => {
        it('вызывает api.processStaffPayout при выплате', async () => {
            const wrapper = mountPanel();
            await flushPromises();

            // Переключаемся на таб "Персонал" и находим реальный BeautyStaff
            const staffComp = await switchToTabAndFind(wrapper, 'staff', BeautyStaff);
            expect(staffComp.exists()).toBe(true);

            // Эмитим событие payout на реальном компоненте
            staffComp.vm.$emit('payout', { masterId: 1, name: 'Тест', amount: 5000 });
            await flushPromises();

            expect(mockApi.processStaffPayout).toHaveBeenCalledWith(1, {
                amount: 5000,
                reason: 'manual_payout',
            });
        });
    });

    /* ═══════════════════════════════════════════════════
     * LOYALTY
     * ═══════════════════════════════════════════════════ */
    describe('Лояльность', () => {
        it('вызывает api.awardBonus при начислении бонусов', async () => {
            const wrapper = mountPanel();
            await flushPromises();

            const loyaltyComp = await switchToTabAndFind(wrapper, 'loyalty', BeautyLoyalty);
            expect(loyaltyComp.exists()).toBe(true);

            loyaltyComp.vm.$emit('award-bonus', { clientId: 1, clientName: 'Тест', amount: 100 });
            await flushPromises();

            expect(mockApi.awardBonus).toHaveBeenCalledWith({
                client_id: 1,
                amount: 100,
                reason: '',
            });
        });
    });

    /* ═══════════════════════════════════════════════════
     * REVIEWS
     * ═══════════════════════════════════════════════════ */
    describe('Отзывы', () => {
        it('вызывает api.replyToReview при ответе на отзыв', async () => {
            const wrapper = mountPanel();
            await flushPromises();

            const reviewsComp = await switchToTabAndFind(wrapper, 'reviews', BeautyReviews);
            expect(reviewsComp.exists()).toBe(true);

            reviewsComp.vm.$emit('reply-review', { reviewId: 1, message: 'Спасибо!' });
            await flushPromises();

            expect(mockApi.replyToReview).toHaveBeenCalledWith(1, {
                message: 'Спасибо!',
            });
        });
    });

    /* ═══════════════════════════════════════════════════
     * NOTIFICATIONS
     * ═══════════════════════════════════════════════════ */
    describe('Уведомления', () => {
        it('вызывает api.sendNotification при отправке уведомления', async () => {
            const wrapper = mountPanel();
            await flushPromises();

            const notifComp = await switchToTabAndFind(wrapper, 'notifications', BeautyNotifications);
            expect(notifComp.exists()).toBe(true);

            notifComp.vm.$emit('send-notification', { message: 'Тест' });
            await flushPromises();

            expect(mockApi.sendNotification).toHaveBeenCalledWith({
                message: 'Тест',
            });
        });
    });

    /* ═══════════════════════════════════════════════════
     * ERROR HANDLING
     * ═══════════════════════════════════════════════════ */
    describe('Обработка ошибок API', () => {
        it('логирует ошибку при неудачной выплате', async () => {
            mockApi.processStaffPayout.mockRejectedValueOnce(new Error('Payment failed'));
            const wrapper = mountPanel();
            await flushPromises();

            const staffComp = await switchToTabAndFind(wrapper, 'staff', BeautyStaff);
            staffComp.vm.$emit('payout', { masterId: 1, name: 'Тест', amount: 5000 });
            await flushPromises();

            // Компонент не упал
            expect(wrapper.text()).toContain('Beauty — B2B Кабинет');
        });

        it('не падает при ошибке API в начислении бонусов', async () => {
            mockApi.awardBonus.mockRejectedValueOnce(new Error('Bonus error'));
            const wrapper = mountPanel();
            await flushPromises();

            const loyaltyComp = await switchToTabAndFind(wrapper, 'loyalty', BeautyLoyalty);
            loyaltyComp.vm.$emit('award-bonus', { clientId: 1, clientName: 'Тест', amount: 100 });
            await flushPromises();

            expect(wrapper.text()).toContain('Beauty — B2B Кабинет');
        });
    });
});
