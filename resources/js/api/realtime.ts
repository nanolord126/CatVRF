import axios, { type AxiosInstance, type AxiosResponse } from 'axios';

/**
 * Real-time API Client
 *
 * Методы:
 * - trackPresence(status, location)
 * - getOnlineUsers()
 * - subscribe(channel)
 * - unsubscribe(channel)
 */

export interface PresenceLocation {
    lat: number;
    lon: number;
}

export interface OnlineUser {
    id: number;
    name: string;
    status: string;
    last_seen_at: string;
}

export interface ChannelInfo {
    name: string;
    members_count: number;
}

export interface ApiData<T = unknown> {
    data: T;
    message?: string;
}

const apiClient: AxiosInstance = axios.create({
    baseURL: '/api/v2/realtime',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

// Add auth token to requests
apiClient.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export const realtimeApi = {
    /** Track user presence */
    trackPresence: async (status: string = 'online', location: PresenceLocation | null = null): Promise<ApiData> => {
        const response: AxiosResponse<ApiData> = await apiClient.post('/presence', {
            status,
            location,
        });
        return response.data;
    },

    /** Get online users */
    getOnlineUsers: async (): Promise<ApiData<OnlineUser[]>> => {
        const response: AxiosResponse<ApiData<OnlineUser[]>> = await apiClient.get('/online');
        return response.data;
    },

    /** Stop tracking presence */
    stopTracking: async (): Promise<ApiData> => {
        const response: AxiosResponse<ApiData> = await apiClient.delete('/presence');
        return response.data;
    },

    /** Subscribe to channel */
    subscribe: async (channel: string): Promise<ApiData> => {
        const response: AxiosResponse<ApiData> = await apiClient.post('/subscribe', { channel });
        return response.data;
    },

    /** Unsubscribe from channel */
    unsubscribe: async (channel: string): Promise<ApiData> => {
        const response: AxiosResponse<ApiData> = await apiClient.delete('/unsubscribe', {
            data: { channel },
        });
        return response.data;
    },

    /** Get subscribed channels */
    getChannels: async (): Promise<ApiData<ChannelInfo[]>> => {
        const response: AxiosResponse<ApiData<ChannelInfo[]>> = await apiClient.get('/channels');
        return response.data;
    },
};

export default realtimeApi;
