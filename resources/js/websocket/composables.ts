/**
 * Vue Composable for Real-time Updates
 */

import { ref, onMounted, onUnmounted } from 'vue';
import { RealtimeClient } from './client';

export interface UseConcertUpdatesOptions {
  concertId: number;
  onUpdate?: (data: any) => void;
  onDelete?: (id: number) => void;
  onError?: (error: Error) => void;
}

export function useConcertUpdates(options: UseConcertUpdatesOptions) {
  const concert = ref<any>(null);
  const isConnected = ref(false);
  const error = ref<string | null>(null);

  let client: RealtimeClient | null = null;

  const connect = async () => {
    try {
      const token = localStorage.getItem('auth_token');
      if (!token) throw new Error('No auth token found');

      client = new RealtimeClient(
        `${window.location.protocol === 'https:' ? 'wss:' : 'ws:'}//${window.location.host}/ws`,
        token
      );

      await client.connect();
      isConnected.value = true;

      // Subscribe to concert updates
      client.subscribe(`concerts.${options.concertId}`);

      // Listen for concert updates
      client.on('concert.updated', (data) => {
        concert.value = data;
        options.onUpdate?.(data);
      });

      // Listen for concert deletion
      client.on('concert.deleted', (data) => {
        options.onDelete?.(data.id);
      });
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Connection failed';
      error.value = errorMessage;
      options.onError?.(new Error(errorMessage));
    }
  };

  const disconnect = () => {
    if (client) {
      client.disconnect();
      isConnected.value = false;
    }
  };

  onMounted(() => {
    connect();
  });

  onUnmounted(() => {
    disconnect();
  });

  return {
    concert,
    isConnected,
    error,
    disconnect,
  };
}

export interface UseUserNotificationsOptions {
  onNotification?: (type: string, data: any) => void;
  onError?: (error: Error) => void;
}

export function useUserNotifications(options: UseUserNotificationsOptions) {
  const notifications = ref<any[]>([]);
  const isConnected = ref(false);

  let client: RealtimeClient | null = null;

  const connect = async () => {
    try {
      const token = localStorage.getItem('auth_token');
      const userId = localStorage.getItem('user_id');

      if (!token || !userId) throw new Error('No auth data found');

      client = new RealtimeClient(
        `${window.location.protocol === 'https:' ? 'wss:' : 'ws:'}//${window.location.host}/ws`,
        token
      );

      await client.connect();
      isConnected.value = true;

      // Subscribe to user notifications
      client.subscribe(`user.${userId}`, true);

      // Listen for all notification types
      client.on('notification.concert_update', (data) => {
        notifications.value.push({ type: 'concert_update', data, timestamp: Date.now() });
        options.onNotification?.('concert_update', data);
      });

      client.on('notification.order_status', (data) => {
        notifications.value.push({ type: 'order_status', data, timestamp: Date.now() });
        options.onNotification?.('order_status', data);
      });

      client.on('notification.payment_received', (data) => {
        notifications.value.push({ type: 'payment_received', data, timestamp: Date.now() });
        options.onNotification?.('payment_received', data);
      });
    } catch (err) {
      options.onError?.(err instanceof Error ? err : new Error('Connection failed'));
    }
  };

  const disconnect = () => {
    if (client) {
      client.disconnect();
      isConnected.value = false;
    }
  };

  const clearNotifications = () => {
    notifications.value = [];
  };

  onMounted(() => {
    connect();
  });

  onUnmounted(() => {
    disconnect();
  });

  return {
    notifications,
    isConnected,
    clearNotifications,
    disconnect,
  };
}
