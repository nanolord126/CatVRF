/**
 * CatVRF 2026 — Centralized API Client
 * Axios-клиент с interceptors: tenant-id, correlation-id, auth token, error handling
 */

import axios from 'axios';
import type { AxiosInstance, AxiosError, InternalAxiosRequestConfig } from 'axios';

const apiClient: AxiosInstance = axios.create({
    baseURL: '/api/v1',
    timeout: 30000,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
    withCredentials: true,
});

/* ── Request Interceptor ───────────────────────────────────────────── */
apiClient.interceptors.request.use(
    (config: InternalAxiosRequestConfig) => {
        // Correlation ID
        config.headers.set('X-Correlation-ID', crypto.randomUUID());

        // CSRF token (Laravel Sanctum)
        const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
        if (csrfToken) {
            config.headers.set('X-CSRF-TOKEN', csrfToken);
        }

        // Tenant ID из meta-тега или localStorage
        const tenantId = document.querySelector<HTMLMetaElement>('meta[name="tenant-id"]')?.content
            ?? localStorage.getItem('tenant_id');
        if (tenantId) {
            config.headers.set('X-Tenant-ID', tenantId);
        }

        // Business Group ID (B2B)
        const bgId = localStorage.getItem('active_business_group_id');
        if (bgId) {
            config.headers.set('X-Business-Group-ID', bgId);
        }

        return config;
    },
    (error) => Promise.reject(error),
);

/* ── Response Interceptor ──────────────────────────────────────────── */
apiClient.interceptors.response.use(
    (response) => response,
    (error: AxiosError) => {
        const status = error.response?.status;

        if (status === 401) {
            // Unauthenticated — redirect to login
            window.location.href = '/login';
            return Promise.reject(error);
        }

        if (status === 403) {
            // Forbidden — dispatch toast через CustomEvent
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    type: 'error',
                    title: 'Доступ запрещён',
                    message: 'У вас нет прав для этого действия',
                },
            }));
            return Promise.reject(error);
        }

        if (status === 419) {
            // CSRF token expired — reload
            window.location.reload();
            return Promise.reject(error);
        }

        if (status === 422) {
            // Validation errors — pass through for form handling
            return Promise.reject(error);
        }

        if (status === 429) {
            // Rate limit
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    type: 'warning',
                    title: 'Слишком много запросов',
                    message: 'Пожалуйста, подождите немного',
                },
            }));
            return Promise.reject(error);
        }

        if (status && status >= 500) {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    type: 'error',
                    title: 'Ошибка сервера',
                    message: 'Произошла ошибка. Пожалуйста, попробуйте позже.',
                },
            }));
        }

        return Promise.reject(error);
    },
);

export default apiClient;

export { apiClient };
