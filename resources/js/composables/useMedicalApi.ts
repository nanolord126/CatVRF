/**
 * useMedicalApi — composable для всех API-запросов Medical-вертикали.
 *
 * Централизованный слой связи фронтенда и бэкенда.
 * Все компоненты Medical получают данные ТОЛЬКО через этот composable.
 * Никаких хардкод-массивов, никаких mock-данных в продакшене.
 *
 * API prefix: /api/v1/medical
 * Авторизация: auth:sanctum (cookie-based через Inertia)
 * Заголовки: X-Correlation-ID (автогенерация UUID)
 * Compliance: 152-ФЗ, ФЗ-323 - все медицинские данные анонимизированы перед отправкой во внешние API
 */
import { ref, readonly } from 'vue';
import axios, { type AxiosResponse, type AxiosError } from 'axios';

/* ─── Types ─── */
export interface MedicalDoctor {
    id: number;
    uuid: string;
    full_name: string;
    specialization: string;
    license_number: string;
    rating: number;
    is_verified: boolean;
    is_active: boolean;
    created_at: string;
}

export interface MedicalAppointment {
    id: number;
    uuid: string;
    doctor_id: number;
    patient_id: number;
    clinic_id: number;
    status: 'pending' | 'confirmed' | 'in_progress' | 'completed' | 'cancelled';
    starts_at: string;
    ends_at: string;
    reason: string;
    diagnosis?: string;
    prescription?: string;
    is_emergency: boolean;
    created_at: string;
}

export interface MedicalClinic {
    id: number;
    uuid: string;
    name: string;
    address: string;
    lat: number;
    lon: number;
    phone: string;
    is_24h: boolean;
    is_verified: boolean;
    rating: number;
    created_at: string;
}

export interface MedicalRecord {
    id: number;
    uuid: string;
    patient_id: number;
    doctor_id: number;
    record_type: string;
    content: string;
    is_anonymized: boolean;
    created_at: string;
}

export interface HealthScore {
    patient_id: number;
    score: number;
    factors: Record<string, number>;
    last_updated: string;
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

const API_BASE = '/api/v1/medical';

/* ─── Composable ─── */
export function useMedicalApi() {
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
     * DOCTORS
     * ═══════════════════════════════════════════════════ */
    async function fetchDoctors(params?: Record<string, unknown>): Promise<MedicalDoctor[]> {
        const result = await apiGet<{ data: MedicalDoctor[] }>('/doctors', params);
        return result !== null && result.data !== undefined ? result.data : [];
    }

    async function fetchDoctor(id: number): Promise<MedicalDoctor | null> {
        const result = await apiGet<{ data: MedicalDoctor }>(`/doctors/${id}`);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function createDoctor(data: Partial<MedicalDoctor>): Promise<MedicalDoctor | null> {
        const result = await apiPost<{ data: MedicalDoctor }>('/doctors', data as Record<string, unknown>);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function updateDoctor(id: number, data: Partial<MedicalDoctor>): Promise<MedicalDoctor | null> {
        const result = await apiPut<{ data: MedicalDoctor }>(`/doctors/${id}`, data as Record<string, unknown>);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function deleteDoctor(id: number): Promise<boolean> {
        const result = await apiDelete(`/doctors/${id}`);
        return result !== null;
    }

    /* ═══════════════════════════════════════════════════
     * APPOINTMENTS
     * ═══════════════════════════════════════════════════ */
    async function fetchAppointments(params?: Record<string, unknown>): Promise<MedicalAppointment[]> {
        const result = await apiGet<{ data: MedicalAppointment[] }>('/appointments', params);
        return result !== null && result.data !== undefined ? result.data : [];
    }

    async function fetchAppointment(id: number): Promise<MedicalAppointment | null> {
        const result = await apiGet<{ data: MedicalAppointment }>(`/appointments/${id}`);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function createAppointment(data: Record<string, unknown>): Promise<MedicalAppointment | null> {
        const result = await apiPost<{ data: MedicalAppointment }>('/appointments', data);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function updateAppointment(id: number, data: Partial<MedicalAppointment>): Promise<MedicalAppointment | null> {
        const result = await apiPut<{ data: MedicalAppointment }>(`/appointments/${id}`, data as Record<string, unknown>);
        return result !== null && result.data !== undefined ? result.data : null;
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
     * CLINICS
     * ═══════════════════════════════════════════════════ */
    async function fetchClinics(params?: Record<string, unknown>): Promise<MedicalClinic[]> {
        const result = await apiGet<{ data: MedicalClinic[] }>('/clinics', params);
        return result !== null && result.data !== undefined ? result.data : [];
    }

    async function fetchClinic(id: number): Promise<MedicalClinic | null> {
        const result = await apiGet<{ data: MedicalClinic }>(`/clinics/${id}`);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function createClinic(data: Partial<MedicalClinic>): Promise<MedicalClinic | null> {
        const result = await apiPost<{ data: MedicalClinic }>('/clinics', data as Record<string, unknown>);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function updateClinic(id: number, data: Partial<MedicalClinic>): Promise<MedicalClinic | null> {
        const result = await apiPut<{ data: MedicalClinic }>(`/clinics/${id}`, data as Record<string, unknown>);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    /* ═══════════════════════════════════════════════════
     * MEDICAL RECORDS (152-ФЗ COMPLIANT)
     * ═══════════════════════════════════════════════════ */
    async function fetchMedicalRecords(params?: Record<string, unknown>): Promise<MedicalRecord[]> {
        const result = await apiGet<{ data: MedicalRecord[] }>('/records', params);
        return result !== null && result.data !== undefined ? result.data : [];
    }

    async function fetchMedicalRecord(id: number): Promise<MedicalRecord | null> {
        const result = await apiGet<{ data: MedicalRecord }>(`/records/${id}`);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function createMedicalRecord(data: Record<string, unknown>): Promise<MedicalRecord | null> {
        const result = await apiPost<{ data: MedicalRecord }>('/records', data);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function updateMedicalRecord(id: number, data: Partial<MedicalRecord>): Promise<MedicalRecord | null> {
        const result = await apiPut<{ data: MedicalRecord }>(`/records/${id}`, data as Record<string, unknown>);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    /* ═══════════════════════════════════════════════════
     * HEALTH SCORE
     * ═══════════════════════════════════════════════════ */
    async function fetchHealthScore(patientId: number): Promise<HealthScore | null> {
        const result = await apiGet<{ data: HealthScore }>(`/health-score/${patientId}`);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function calculateHealthScore(patientId: number): Promise<HealthScore | null> {
        const result = await apiPost<{ data: HealthScore }>(`/health-score/${patientId}/calculate`);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    /* ═══════════════════════════════════════════════════
     * AI DIAGNOSIS (ANONYMIZED BEFORE EXTERNAL API)
     * ═══════════════════════════════════════════════════ */
    async function runAIDiagnosis(data: Record<string, unknown>): Promise<Record<string, unknown> | null> {
        const result = await apiPost<Record<string, unknown>>('/ai/diagnosis', data);
        return result;
    }

    async function getAIRecommendations(patientId: number): Promise<Record<string, unknown> | null> {
        const result = await apiGet<Record<string, unknown>>(`/ai/recommendations/${patientId}`);
        return result;
    }

    /* ═══════════════════════════════════════════════════
     * DASHBOARD / ANALYTICS
     * ═══════════════════════════════════════════════════ */
    async function fetchDashboard(): Promise<Record<string, unknown> | null> {
        return apiGet<Record<string, unknown>>('/dashboard');
    }

    async function fetchAnalytics(params?: Record<string, unknown>): Promise<Record<string, unknown> | null> {
        return apiGet<Record<string, unknown>>('/analytics', params);
    }

    return {
        /* state */
        loading: readonly(loading),
        error: readonly(error),
        correlationId: readonly(correlationId),

        /* utils */
        resetError,

        /* doctors */
        fetchDoctors,
        fetchDoctor,
        createDoctor,
        updateDoctor,
        deleteDoctor,

        /* appointments */
        fetchAppointments,
        fetchAppointment,
        createAppointment,
        updateAppointment,
        cancelAppointment,
        confirmAppointment,

        /* clinics */
        fetchClinics,
        fetchClinic,
        createClinic,
        updateClinic,

        /* medical records */
        fetchMedicalRecords,
        fetchMedicalRecord,
        createMedicalRecord,
        updateMedicalRecord,

        /* health score */
        fetchHealthScore,
        calculateHealthScore,

        /* ai */
        runAIDiagnosis,
        getAIRecommendations,

        /* analytics */
        fetchDashboard,
        fetchAnalytics,
    };
}
