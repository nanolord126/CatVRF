/**
 * Глобальная настройка тестовой среды для Vue-компонентов.
 *
 * Мокает внешние зависимости, которые не нужны в unit-тестах:
 * - axios (все HTTP-запросы)
 * - @inertiajs/vue3 (навигация Inertia)
 * - CSS custom properties (темизация)
 */
import { vi, beforeEach, afterEach } from 'vitest';
import { config } from '@vue/test-utils';

/* ─── Mock axios глобально ─── */
vi.mock('axios', () => {
    const mockAxios = {
        create: vi.fn(() => mockAxios),
        get: vi.fn(() => Promise.resolve({ data: {} })),
        post: vi.fn(() => Promise.resolve({ data: {} })),
        put: vi.fn(() => Promise.resolve({ data: {} })),
        delete: vi.fn(() => Promise.resolve({ data: {} })),
        patch: vi.fn(() => Promise.resolve({ data: {} })),
        defaults: { headers: { common: {} } },
        interceptors: {
            request: { use: vi.fn() },
            response: { use: vi.fn() },
        },
    };
    return { default: mockAxios, __esModule: true };
});

/* ─── Mock @inertiajs/vue3 ─── */
vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(() => ({
        props: {
            auth: { user: { id: 1, name: 'Test Admin', tenant_id: 1 } },
            errors: {},
        },
    })),
    router: {
        visit: vi.fn(),
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
        reload: vi.fn(),
    },
    Head: { name: 'Head', template: '<div><slot /></div>' },
    Link: { name: 'Link', template: '<a><slot /></a>', props: ['href'] },
}));

/* ─── Глобальные заглушки для UI-компонентов ─── */
config.global.stubs = {
    Head: true,
    Link: true,
    teleport: true,
    transition: false,
};

/* ─── CSS custom properties fallback ─── */
if (typeof document !== 'undefined') {
    const style = document.createElement('style');
    style.textContent = `
        :root {
            --t-primary: #22c55e;
            --t-primary-bg: rgba(34,197,94,0.1);
            --t-surface: #1a1a2e;
            --t-bg: #0f0f1e;
            --t-text: #e2e8f0;
            --t-text-2: #94a3b8;
            --t-border: #2d2d44;
            --t-accent: #8b5cf6;
        }
    `;
    document.head.appendChild(style);
}

/* ─── Reset mocks between tests ─── */
beforeEach(() => {
    vi.clearAllMocks();
});

afterEach(() => {
    vi.restoreAllMocks();
});
