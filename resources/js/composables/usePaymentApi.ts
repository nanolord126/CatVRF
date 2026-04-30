/**
 * usePaymentApi — composable для всех API-запросов Payment-вертикали.
 *
 * Централизованный слой связи фронтенда и бэкенда.
 * Все компоненты Payment получают данные ТОЛЬКО через этот composable.
 * Никаких хардкод-массивов, никаких mock-данных в продакшене.
 *
 * API prefix: /api/v1/payment
 * Авторизация: auth:sanctum (cookie-based через Inertia)
 * Заголовки: X-Correlation-ID (автогенерация UUID)
 */
import { ref, readonly } from 'vue';
import axios, { type AxiosResponse, type AxiosError } from 'axios';

/* ─── Types ─── */
export interface PaymentItem {
    id: number;
    uuid: string;
    name: string;
    status: string;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

export interface ApiError {
    message: string;
    errors?: Record<string, string[]>;
    correlation_id?: string;
}

/* ─── Helpers ─── */
function generateCorrelationId(): string {
    return crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random().toString(36).slice(2, 11)}`;
}

function buildHeaders(correlationId?: string): Record<string, string> {
    return {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Correlation-ID': correlationId !== undefined ? correlationId : generateCorrelationId(),
        'X-Requested-With': 'XMLHttpRequest',
    };
}

const API_BASE = '/api/v1/payment';

/* ─── Composable ─── */
export function usePaymentApi() {
    const loading = ref(false);
    const error = ref<ApiError | null>(null);
    const correlationId = ref(generateCorrelationId());

    function resetError(): void {
        error.value = null;
    }

    function handleError(err: AxiosError<ApiError>): void {
        if (err.response?.data) {
            error.value = {
                message: err.response.data.message !== undefined ? err.response.data.message : 'Ошибка сервера',
                errors: err.response.data.errors,
                correlation_id: err.response.data.correlation_id,
            };
        } else if (err.request) {
            error.value = { message: 'Сервер не отвечает. Проверьте соединение.' };
        } else {
            error.value = { message: err.message !== undefined ? err.message : 'Неизвестная ошибка' };
        }
    }

    async function apiGet<T>(url: string, params?: Record<string, unknown>): Promise<T | null> {
        loading.value = true;
        resetError();
        try {
            const response: AxiosResponse<T> = await axios.get(`${API_BASE}${url}`, {
                headers: buildHeaders(correlationId.value),
                params,
            });
            return response.data;
        } catch (err) {
            handleError(err as AxiosError<ApiError>);
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function apiPost<T>(url: string, data?: Record<string, unknown>): Promise<T | null> {
        loading.value = true;
        resetError();
        try {
            const response: AxiosResponse<T> = await axios.post(`${API_BASE}${url}`, data, {
                headers: buildHeaders(correlationId.value),
            });
            return response.data;
        } catch (err) {
            handleError(err as AxiosError<ApiError>);
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function apiPut<T>(url: string, data?: Record<string, unknown>): Promise<T | null> {
        loading.value = true;
        resetError();
        try {
            const response: AxiosResponse<T> = await axios.put(`${API_BASE}${url}`, data, {
                headers: buildHeaders(correlationId.value),
            });
            return response.data;
        } catch (err) {
            handleError(err as AxiosError<ApiError>);
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function apiDelete<T>(url: string): Promise<T | null> {
        loading.value = true;
        resetError();
        try {
            const response: AxiosResponse<T> = await axios.delete(`${API_BASE}${url}`, {
                headers: buildHeaders(correlationId.value),
            });
            return response.data;
        } catch (err) {
            handleError(err as AxiosError<ApiError>);
            return null;
        } finally {
            loading.value = false;
        }
    }

    /* ═══════════════════════════════════════════════════
     * CRUD OPERATIONS
     * ═══════════════════════════════════════════════════ */
    async function fetchItems(params?: Record<string, unknown>): Promise<PaymentItem[]> {
        const result = await apiGet<{ data: PaymentItem[] }>('/', params);
        return result !== null && result.data !== undefined ? result.data : [];
    }

    async function fetchItem(id: number): Promise<PaymentItem | null> {
        const result = await apiGet<{ data: PaymentItem }>(`/${id}`);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function createItem(data: Partial<PaymentItem>): Promise<PaymentItem | null> {
        const result = await apiPost<{ data: PaymentItem }>('/', data as Record<string, unknown>);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function updateItem(id: number, data: Partial<PaymentItem>): Promise<PaymentItem | null> {
        const result = await apiPut<{ data: PaymentItem }>(`/${id}`, data as Record<string, unknown>);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function deleteItem(id: number): Promise<boolean> {
        const result = await apiDelete(`/ ${id}`);
        return result !== null;
    }

    /* ═══════════════════════════════════════════════════
     * DASHBOARD / ANALYTICS
     * ═══════════════════════════════════════════════════ */
    async function fetchDashboard(): Promise<Record<string, unknown> | null> {
        return apiGet('/dashboard');
    }

    async function fetchAnalytics(params?: Record<string, unknown>): Promise<Record<string, unknown> | null> {
        return apiGet('/analytics', params);
    }

    return {
        /* state */
        loading: readonly(loading),
        error: readonly(error),
        correlationId: readonly(correlationId),

        /* utils */
        resetError,

        /* crud */
        fetchItems,
        fetchItem,
        createItem,
        updateItem,
        deleteItem,

        /* analytics */
        fetchDashboard,
        fetchAnalytics,
    };
}