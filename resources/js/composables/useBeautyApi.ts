/**
 * useBeautyApi — composable для всех API-запросов Beauty-вертикали.
 *
 * Централизованный слой связи фронтенда и бэкенда.
 * Все компоненты Beauty получают данные ТОЛЬКО через этот composable.
 * Никаких хардкод-массивов, никаких mock-данных в продакшене.
 *
 * API prefix: /api/v1/beauty
 * Авторизация: auth:sanctum (cookie-based через Inertia)
 * Заголовки: X-Correlation-ID (автогенерация UUID)
 */
import { ref, reactive, readonly } from 'vue';
import axios, { type AxiosResponse, type AxiosError } from 'axios';

/* ─── Types ─── */
export interface BeautySalon {
    id: number;
    uuid: string;
    name: string;
    address: string;
    lat: number;
    lon: number;
    status: string;
    tags: string[];
    rating: number;
    is_active: boolean;
    created_at: string;
}

export interface BeautyMaster {
    id: number;
    salon_id: number;
    full_name: string;
    specialization: string;
    rating: number;
    is_active: boolean;
    tags: string[];
    portfolio: string[];
    schedule: Record<string, unknown>;
}

export interface BeautyService {
    id: number;
    name: string;
    category: string;
    price_b2c: number;
    price_b2b: number;
    duration_minutes: number;
    is_active: boolean;
}

export interface BeautyAppointment {
    id: number;
    salon_id: number;
    master_id: number;
    service_id: number;
    user_id: number;
    status: string;
    starts_at: string;
    ends_at: string;
    is_b2b: boolean;
    client_name?: string;
    service_name?: string;
    master_name?: string;
}

export interface BeautyReview {
    id: number;
    user_id: number;
    salon_id: number;
    master_id: number;
    rating: number;
    text: string;
    status: string;
    created_at: string;
    client_name?: string;
}

export interface BeautyProduct {
    id: number;
    name: string;
    sku: string;
    price: number;
    stock: number;
    category: string;
}

export interface BeautyConsumable {
    id: number;
    name: string;
    quantity: number;
    min_quantity: number;
    unit: string;
}

export interface DashboardStats {
    revenue_today: number;
    revenue_week: number;
    active_bookings: number;
    masters_load: number;
    avg_check: number;
    conversion: number;
    trends: Record<string, string>;
}

export interface FinanceStats {
    revenue: number;
    expenses: number;
    profit: number;
    commission: number;
    payouts_pending: number;
    revenue_by_service: Array<{ service: string; amount: number }>;
    revenue_by_channel: Array<{ channel: string; amount: number }>;
    master_payouts: Array<{ master_id: number; name: string; amount: number; status: string }>;
}

export interface LoyaltyConfig {
    tiers: Array<{ name: string; min_spend: number; cashback_pct: number; color: string }>;
    referral_bonus: number;
    birthday_bonus: number;
    rules: Array<{ id: number; name: string; type: string; value: number; is_active: boolean }>;
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
        'X-Correlation-ID': correlationId || generateCorrelationId(),
        'X-Requested-With': 'XMLHttpRequest',
    };
}

const API_BASE = '/api/v1/beauty';

/* ─── Composable ─── */
export function useBeautyApi() {
    const loading = ref(false);
    const error = ref<ApiError | null>(null);
    const correlationId = ref(generateCorrelationId());

    function resetError(): void {
        error.value = null;
    }

    function handleError(err: AxiosError<ApiError>): void {
        if (err.response?.data) {
            error.value = {
                message: err.response.data.message || 'Ошибка сервера',
                errors: err.response.data.errors,
                correlation_id: err.response.data.correlation_id,
            };
        } else if (err.request) {
            error.value = { message: 'Сервер не отвечает. Проверьте соединение.' };
        } else {
            error.value = { message: err.message || 'Неизвестная ошибка' };
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
     * SALONS
     * ═══════════════════════════════════════════════════ */
    async function fetchSalons(params?: Record<string, unknown>): Promise<BeautySalon[]> {
        const result = await apiGet<{ data: BeautySalon[] }>('/salons', params);
        return result?.data ?? [];
    }

    async function fetchSalon(id: number): Promise<BeautySalon | null> {
        const result = await apiGet<{ data: BeautySalon }>(`/salons/${id}`);
        return result?.data ?? null;
    }

    async function createSalon(data: Partial<BeautySalon>): Promise<BeautySalon | null> {
        const result = await apiPost<{ data: BeautySalon }>('/salons', data as Record<string, unknown>);
        return result?.data ?? null;
    }

    async function updateSalon(id: number, data: Partial<BeautySalon>): Promise<BeautySalon | null> {
        const result = await apiPut<{ data: BeautySalon }>(`/salons/${id}`, data as Record<string, unknown>);
        return result?.data ?? null;
    }

    async function deleteSalon(id: number): Promise<boolean> {
        const result = await apiDelete(`/salons/${id}`);
        return result !== null;
    }

    async function fetchSalonAvailability(salonId: number, date?: string): Promise<unknown> {
        return apiGet(`/salons/${salonId}/availability`, date ? { date } : undefined);
    }

    /* ═══════════════════════════════════════════════════
     * MASTERS
     * ═══════════════════════════════════════════════════ */
    async function fetchMasters(params?: Record<string, unknown>): Promise<BeautyMaster[]> {
        const result = await apiGet<{ data: BeautyMaster[] }>('/masters', params);
        return result?.data ?? [];
    }

    async function fetchMaster(id: number): Promise<BeautyMaster | null> {
        const result = await apiGet<{ data: BeautyMaster }>(`/masters/${id}`);
        return result?.data ?? null;
    }

    async function createMaster(data: Partial<BeautyMaster>): Promise<BeautyMaster | null> {
        const result = await apiPost<{ data: BeautyMaster }>('/masters', data as Record<string, unknown>);
        return result?.data ?? null;
    }

    async function updateMaster(id: number, data: Partial<BeautyMaster>): Promise<BeautyMaster | null> {
        const result = await apiPut<{ data: BeautyMaster }>(`/masters/${id}`, data as Record<string, unknown>);
        return result?.data ?? null;
    }

    async function deleteMaster(id: number): Promise<boolean> {
        const result = await apiDelete(`/masters/${id}`);
        return result !== null;
    }

    async function fetchMasterPortfolio(masterId: number): Promise<unknown> {
        return apiGet(`/masters/${masterId}/portfolio`);
    }

    async function fetchMasterSchedule(masterId: number, date?: string): Promise<unknown> {
        return apiGet(`/masters/${masterId}/schedule`, date ? { date } : undefined);
    }

    /* ═══════════════════════════════════════════════════
     * SERVICES
     * ═══════════════════════════════════════════════════ */
    async function fetchServices(params?: Record<string, unknown>): Promise<BeautyService[]> {
        const result = await apiGet<{ data: BeautyService[] }>('/services', params);
        return result?.data ?? [];
    }

    async function createService(data: Partial<BeautyService>): Promise<BeautyService | null> {
        const result = await apiPost<{ data: BeautyService }>('/services', data as Record<string, unknown>);
        return result?.data ?? null;
    }

    async function updateService(id: number, data: Partial<BeautyService>): Promise<BeautyService | null> {
        const result = await apiPut<{ data: BeautyService }>(`/services/${id}`, data as Record<string, unknown>);
        return result?.data ?? null;
    }

    async function deleteService(id: number): Promise<boolean> {
        const result = await apiDelete(`/services/${id}`);
        return result !== null;
    }

    /* ═══════════════════════════════════════════════════
     * APPOINTMENTS / BOOKINGS
     * ═══════════════════════════════════════════════════ */
    async function fetchAppointments(params?: Record<string, unknown>): Promise<BeautyAppointment[]> {
        const result = await apiGet<{ data: BeautyAppointment[] }>('/appointments', params);
        return result?.data ?? [];
    }

    async function createAppointment(data: Record<string, unknown>): Promise<BeautyAppointment | null> {
        const result = await apiPost<{ data: BeautyAppointment }>('/appointments', data);
        return result?.data ?? null;
    }

    async function cancelAppointment(id: number): Promise<boolean> {
        const result = await apiPost(`/appointments/${id}/cancel`);
        return result !== null;
    }

    async function confirmAppointment(id: number): Promise<boolean> {
        const result = await apiPost(`/appointments/${id}/confirm`);
        return result !== null;
    }

    /* ═══════════════════════════════════════════════════
     * REVIEWS
     * ═══════════════════════════════════════════════════ */
    async function fetchReviews(params?: Record<string, unknown>): Promise<BeautyReview[]> {
        const result = await apiGet<{ data: BeautyReview[] }>('/reviews', params);
        return result?.data ?? [];
    }

    async function createReview(data: Record<string, unknown>): Promise<BeautyReview | null> {
        const result = await apiPost<{ data: BeautyReview }>('/reviews', data);
        return result?.data ?? null;
    }

    async function updateReview(id: number, data: Record<string, unknown>): Promise<BeautyReview | null> {
        const result = await apiPut<{ data: BeautyReview }>(`/reviews/${id}`, data);
        return result?.data ?? null;
    }

    async function deleteReview(id: number): Promise<boolean> {
        const result = await apiDelete(`/reviews/${id}`);
        return result !== null;
    }

    async function replyToReview(id: number, data: Record<string, unknown>): Promise<boolean> {
        const result = await apiPost(`/reviews/${id}/reply`, data);
        return result !== null;
    }

    async function flagReview(id: number, data: Record<string, unknown>): Promise<boolean> {
        const result = await apiPost(`/reviews/${id}/flag`, data);
        return result !== null;
    }

    /* ═══════════════════════════════════════════════════
     * PRODUCTS
     * ═══════════════════════════════════════════════════ */
    async function fetchProducts(params?: Record<string, unknown>): Promise<BeautyProduct[]> {
        const result = await apiGet<{ data: BeautyProduct[] }>('/products', params);
        return result?.data ?? [];
    }

    async function createProduct(data: Record<string, unknown>): Promise<BeautyProduct | null> {
        const result = await apiPost<{ data: BeautyProduct }>('/products', data);
        return result?.data ?? null;
    }

    async function updateProduct(id: number, data: Record<string, unknown>): Promise<BeautyProduct | null> {
        const result = await apiPut<{ data: BeautyProduct }>(`/products/${id}`, data);
        return result?.data ?? null;
    }

    async function deleteProduct(id: number): Promise<boolean> {
        const result = await apiDelete(`/products/${id}`);
        return result !== null;
    }

    /* ═══════════════════════════════════════════════════
     * CONSUMABLES / INVENTORY
     * ═══════════════════════════════════════════════════ */
    async function fetchConsumables(params?: Record<string, unknown>): Promise<BeautyConsumable[]> {
        const result = await apiGet<{ data: BeautyConsumable[] }>('/consumables', params);
        return result?.data ?? [];
    }

    async function createConsumable(data: Record<string, unknown>): Promise<BeautyConsumable | null> {
        const result = await apiPost<{ data: BeautyConsumable }>('/consumables', data);
        return result?.data ?? null;
    }

    async function updateConsumable(id: number, data: Record<string, unknown>): Promise<BeautyConsumable | null> {
        const result = await apiPut<{ data: BeautyConsumable }>(`/consumables/${id}`, data);
        return result?.data ?? null;
    }

    async function fetchConsumableLogs(): Promise<unknown> {
        return apiGet('/consumables/logs');
    }

    /* ═══════════════════════════════════════════════════
     * DASHBOARD / ANALYTICS
     * ═══════════════════════════════════════════════════ */
    async function fetchDashboard(): Promise<DashboardStats | null> {
        const result = await apiGet<{ data: DashboardStats }>('/dashboard');
        return result?.data ?? null;
    }

    async function fetchFinanceStats(params?: Record<string, unknown>): Promise<FinanceStats | null> {
        const result = await apiGet<{ data: FinanceStats }>('/analytics/finances', params);
        return result?.data ?? null;
    }

    async function fetchAnalytics(params?: Record<string, unknown>): Promise<unknown> {
        return apiGet('/analytics', params);
    }

    async function fetchReportsData(type: string, params?: Record<string, unknown>): Promise<unknown> {
        return apiGet(`/reports/${type}`, params);
    }

    /* ═══════════════════════════════════════════════════
     * LOYALTY
     * ═══════════════════════════════════════════════════ */
    async function fetchLoyaltyConfig(): Promise<LoyaltyConfig | null> {
        const result = await apiGet<{ data: LoyaltyConfig }>('/loyalty');
        return result?.data ?? null;
    }

    async function updateLoyaltyConfig(data: Record<string, unknown>): Promise<boolean> {
        const result = await apiPut('/loyalty', data);
        return result !== null;
    }

    async function awardBonus(userId: number, amount: number, reason: string): Promise<boolean> {
        const result = await apiPost('/loyalty/award', { user_id: userId, amount, reason });
        return result !== null;
    }

    async function deductBonus(userId: number, amount: number, reason: string): Promise<boolean> {
        const result = await apiPost('/loyalty/deduct', { user_id: userId, amount, reason });
        return result !== null;
    }

    /* ═══════════════════════════════════════════════════
     * NOTIFICATIONS
     * ═══════════════════════════════════════════════════ */
    async function sendNotification(data: Record<string, unknown>): Promise<boolean> {
        const result = await apiPost('/notifications/send', data);
        return result !== null;
    }

    async function sendBulkNotification(data: Record<string, unknown>): Promise<boolean> {
        const result = await apiPost('/notifications/bulk', data);
        return result !== null;
    }

    async function fetchNotificationTemplates(): Promise<unknown> {
        return apiGet('/notifications/templates');
    }

    async function saveNotificationTemplate(data: Record<string, unknown>): Promise<boolean> {
        const result = await apiPost('/notifications/templates', data);
        return result !== null;
    }

    /* ═══════════════════════════════════════════════════
     * STAFF / HR
     * ═══════════════════════════════════════════════════ */
    async function fetchStaff(params?: Record<string, unknown>): Promise<unknown> {
        return apiGet('/staff', params);
    }

    async function addStaffMember(data: Record<string, unknown>): Promise<unknown> {
        return apiPost('/staff', data);
    }

    async function updateStaffMember(id: number, data: Record<string, unknown>): Promise<unknown> {
        return apiPut(`/staff/${id}`, data);
    }

    async function processStaffPayout(id: number, data: Record<string, unknown>): Promise<boolean> {
        const result = await apiPost(`/staff/${id}/payout`, data);
        return result !== null;
    }

    /* ═══════════════════════════════════════════════════
     * CHAT
     * ═══════════════════════════════════════════════════ */
    async function fetchChats(params?: Record<string, unknown>): Promise<unknown> {
        return apiGet('/chats', params);
    }

    async function fetchChatMessages(chatId: number, params?: Record<string, unknown>): Promise<unknown> {
        return apiGet(`/chats/${chatId}/messages`, params);
    }

    async function sendChatMessage(chatId: number, data: Record<string, unknown>): Promise<unknown> {
        return apiPost(`/chats/${chatId}/messages`, data);
    }

    /* ═══════════════════════════════════════════════════
     * AI TRY-ON
     * ═══════════════════════════════════════════════════ */
    async function runAITryOn(formData: FormData): Promise<unknown> {
        loading.value = true;
        resetError();
        try {
            const response = await axios.post(`${API_BASE}/ai/try-on`, formData, {
                headers: {
                    ...buildHeaders(correlationId.value),
                    'Content-Type': 'multipart/form-data',
                },
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
     * CRM / CLIENTS
     * ═══════════════════════════════════════════════════ */
    async function fetchClients(params?: Record<string, unknown>): Promise<unknown> {
        return apiGet('/clients', params);
    }

    async function fetchClient(id: number): Promise<unknown> {
        return apiGet(`/clients/${id}`);
    }

    async function updateClient(id: number, data: Record<string, unknown>): Promise<unknown> {
        return apiPut(`/clients/${id}`, data);
    }

    async function fetchClientSegments(): Promise<unknown> {
        return apiGet('/clients/segments');
    }

    /* ═══════════════════════════════════════════════════
     * PUBLIC PAGES
     * ═══════════════════════════════════════════════════ */
    async function fetchPublicPages(params?: Record<string, unknown>): Promise<unknown> {
        return apiGet('/pages', params);
    }

    async function createPublicPage(data: Record<string, unknown>): Promise<unknown> {
        return apiPost('/pages', data);
    }

    async function updatePublicPage(id: number, data: Record<string, unknown>): Promise<unknown> {
        return apiPut(`/pages/${id}`, data);
    }

    async function deletePublicPage(id: number): Promise<boolean> {
        const result = await apiDelete(`/pages/${id}`);
        return result !== null;
    }

    async function fetchPageStats(params?: Record<string, unknown>): Promise<unknown> {
        return apiGet('/pages/stats', params);
    }

    /* ═══════════════════════════════════════════════════
     * PROMO / MARKETING
     * ═══════════════════════════════════════════════════ */
    async function fetchPromos(params?: Record<string, unknown>): Promise<unknown> {
        return apiGet('/promos', params);
    }

    async function createPromo(data: Record<string, unknown>): Promise<unknown> {
        return apiPost('/promos', data);
    }

    async function updatePromo(id: number, data: Record<string, unknown>): Promise<unknown> {
        return apiPut(`/promos/${id}`, data);
    }

    async function deletePromo(id: number): Promise<boolean> {
        const result = await apiDelete(`/promos/${id}`);
        return result !== null;
    }

    /* ═══════════════════════════════════════════════════
     * EXPORT
     * ═══════════════════════════════════════════════════ */
    async function exportReport(type: string, params?: Record<string, unknown>): Promise<Blob | null> {
        loading.value = true;
        resetError();
        try {
            const response = await axios.get(`${API_BASE}/export/${type}`, {
                headers: buildHeaders(correlationId.value),
                params,
                responseType: 'blob',
            });
            return response.data;
        } catch (err) {
            handleError(err as AxiosError<ApiError>);
            return null;
        } finally {
            loading.value = false;
        }
    }

    function downloadBlob(blob: Blob, filename: string): void {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    return {
        /* state */
        loading: readonly(loading),
        error: readonly(error),
        correlationId: readonly(correlationId),

        /* utils */
        resetError,

        /* salons */
        fetchSalons,
        fetchSalon,
        createSalon,
        updateSalon,
        deleteSalon,
        fetchSalonAvailability,

        /* masters */
        fetchMasters,
        fetchMaster,
        createMaster,
        updateMaster,
        deleteMaster,
        fetchMasterPortfolio,
        fetchMasterSchedule,

        /* services */
        fetchServices,
        createService,
        updateService,
        deleteService,

        /* appointments */
        fetchAppointments,
        createAppointment,
        cancelAppointment,
        confirmAppointment,

        /* reviews */
        fetchReviews,
        createReview,
        updateReview,
        deleteReview,
        replyToReview,
        flagReview,

        /* products */
        fetchProducts,
        createProduct,
        updateProduct,
        deleteProduct,

        /* consumables */
        fetchConsumables,
        createConsumable,
        updateConsumable,
        fetchConsumableLogs,

        /* dashboard / analytics */
        fetchDashboard,
        fetchFinanceStats,
        fetchAnalytics,
        fetchReportsData,

        /* loyalty */
        fetchLoyaltyConfig,
        updateLoyaltyConfig,
        awardBonus,
        deductBonus,

        /* notifications */
        sendNotification,
        sendBulkNotification,
        fetchNotificationTemplates,
        saveNotificationTemplate,

        /* staff */
        fetchStaff,
        addStaffMember,
        updateStaffMember,
        processStaffPayout,

        /* chat */
        fetchChats,
        fetchChatMessages,
        sendChatMessage,

        /* AI */
        runAITryOn,

        /* CRM */
        fetchClients,
        fetchClient,
        updateClient,
        fetchClientSegments,

        /* pages */
        fetchPublicPages,
        createPublicPage,
        updatePublicPage,
        deletePublicPage,
        fetchPageStats,

        /* promos */
        fetchPromos,
        createPromo,
        updatePromo,
        deletePromo,

        /* export */
        exportReport,
        downloadBlob,
    };
}
