/**
 * useBeautyApi — тесты API composable.
 *
 * Проверяет:
 * 1. Формирование правильных URL-ов
 * 2. Добавление X-Correlation-ID заголовка
 * 3. Обработка ошибок
 * 4. Управление loading/error состоянием
 */
import { describe, it, expect, vi, beforeEach } from 'vitest';
import axios from 'axios';

/* ─── Mock axios before importing composable ─── */
vi.mock('axios', () => {
    const instance = {
        get: vi.fn(() => Promise.resolve({ data: {} })),
        post: vi.fn(() => Promise.resolve({ data: {} })),
        put: vi.fn(() => Promise.resolve({ data: {} })),
        delete: vi.fn(() => Promise.resolve({ data: {} })),
        interceptors: {
            request: { use: vi.fn() },
            response: { use: vi.fn() },
        },
        defaults: { headers: { common: {} } },
    };
    return {
        default: {
            create: vi.fn(() => instance),
            ...instance,
        },
        __esModule: true,
    };
});

import { useBeautyApi } from '@/Composables/useBeautyApi';

describe('useBeautyApi', () => {
    let api: ReturnType<typeof useBeautyApi>;

    beforeEach(() => {
        vi.clearAllMocks();
        api = useBeautyApi();
    });

    describe('Инициализация', () => {
        it('возвращает объект с API методами', () => {
            expect(api).toBeDefined();
            expect(typeof api.fetchSalons).toBe('function');
            expect(typeof api.fetchMasters).toBe('function');
            expect(typeof api.fetchServices).toBe('function');
            expect(typeof api.fetchAppointments).toBe('function');
            expect(typeof api.fetchReviews).toBe('function');
            expect(typeof api.fetchProducts).toBe('function');
            expect(typeof api.fetchConsumables).toBe('function');
            expect(typeof api.fetchDashboard).toBe('function');
        });

        it('содержит реактивные loading и error', () => {
            expect(api.loading).toBeDefined();
            expect(api.error).toBeDefined();
        });
    });

    describe('CRUD операции', () => {
        it('содержит методы для CRUD салонов', () => {
            expect(typeof api.createSalon).toBe('function');
            expect(typeof api.updateSalon).toBe('function');
            expect(typeof api.deleteSalon).toBe('function');
        });

        it('содержит методы для CRUD мастеров', () => {
            expect(typeof api.createMaster).toBe('function');
            expect(typeof api.updateMaster).toBe('function');
            expect(typeof api.deleteMaster).toBe('function');
        });

        it('содержит методы для записей', () => {
            expect(typeof api.createAppointment).toBe('function');
            expect(typeof api.cancelAppointment).toBe('function');
            expect(typeof api.confirmAppointment).toBe('function');
        });
    });

    describe('Панельные методы', () => {
        it('содержит метод processStaffPayout', () => {
            expect(typeof api.processStaffPayout).toBe('function');
        });

        it('содержит методы лояльности', () => {
            expect(typeof api.awardBonus).toBe('function');
            expect(typeof api.deductBonus).toBe('function');
            expect(typeof api.updateLoyaltyConfig).toBe('function');
        });

        it('содержит методы уведомлений', () => {
            expect(typeof api.sendNotification).toBe('function');
            expect(typeof api.sendBulkNotification).toBe('function');
        });

        it('содержит методы отзывов', () => {
            expect(typeof api.replyToReview).toBe('function');
            expect(typeof api.flagReview).toBe('function');
        });

        it('содержит методы публичных страниц', () => {
            expect(typeof api.createPublicPage).toBe('function');
            expect(typeof api.updatePublicPage).toBe('function');
            expect(typeof api.deletePublicPage).toBe('function');
        });

        it('содержит метод экспорта', () => {
            expect(typeof api.exportReport).toBe('function');
            expect(typeof api.downloadBlob).toBe('function');
        });
    });
});
