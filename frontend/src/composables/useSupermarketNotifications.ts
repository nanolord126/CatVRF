import { ref, onMounted, onUnmounted } from 'vue';
import { useNotificationWebSocket } from './useNotificationWebSocket';

export interface SupermarketNotification {
  id: string;
  type: 'order_status' | 'batch_arrival' | 'stock_alert' | 'demand_alert';
  title: string;
  message: string;
  data?: Record<string, any>;
  timestamp: string;
  read: boolean;
  priority: 'low' | 'medium' | 'high' | 'critical';
}

export interface OrderStatusNotification extends SupermarketNotification {
  type: 'order_status';
  data: {
    order_id: string;
    order_number: string;
    old_status: string;
    new_status: string;
    delivery_type: 'courier' | 'pickup';
  };
}

export interface BatchArrivalNotification extends SupermarketNotification {
  type: 'batch_arrival';
  data: {
    batch_id: string;
    warehouse_id: string;
    product_count: number;
    total_quantity: number;
    estimated_value: number;
  };
}

export interface StockAlertNotification extends SupermarketNotification {
  type: 'stock_alert';
  data: {
    store_id: string;
    store_name: string;
    product_id: string;
    product_name: string;
    current_stock: number;
    optimal_stock: number;
    priority: 'critical' | 'high' | 'medium';
  };
}

export interface DemandAlertNotification extends SupermarketNotification {
  type: 'demand_alert';
  data: {
    product_id: string;
    product_name: string;
    predicted_demand: number;
    current_stock: number;
    shortage_days: number;
  };
}

export function useSupermarketNotifications() {
  const notifications = ref<SupermarketNotification[]>([]);
  const unreadCount = ref(0);
  const isConnected = ref(false);

  const { connect, disconnect, send } = useNotificationWebSocket();

  // Handle incoming notifications
  const handleNotification = (notification: SupermarketNotification) => {
    notifications.value.unshift(notification);
    unreadCount.value++;
    
    // Show browser notification if permitted
    if (Notification.permission === 'granted') {
      showBrowserNotification(notification);
    }
  };

  // WebSocket message handler
  const handleWebSocketMessage = (message: any) => {
    if (message.channel === 'supermarket') {
      handleNotification(message.data as SupermarketNotification);
    }
  };

  // Show browser notification
  const showBrowserNotification = (notification: SupermarketNotification) => {
    const notificationBody = getNotificationBody(notification);
    
    new Notification(notification.title, {
      body: notificationBody,
      icon: '/icons/supermarket-icon.png',
      tag: notification.id,
      requireInteraction: notification.priority === 'critical' || notification.priority === 'high',
    });
  };

  // Get notification body based on type
  const getNotificationBody = (notification: SupermarketNotification): string => {
    switch (notification.type) {
      case 'order_status':
        return getOrderStatusMessage(notification as OrderStatusNotification);
      case 'batch_arrival':
        return getBatchArrivalMessage(notification as BatchArrivalNotification);
      case 'stock_alert':
        return getStockAlertMessage(notification as StockAlertNotification);
      case 'demand_alert':
        return getDemandAlertMessage(notification as DemandAlertNotification);
      default:
        return notification.message;
    }
  };

  // Order status message
  const getOrderStatusMessage = (notification: OrderStatusNotification): string => {
    const { order_number, new_status, delivery_type } = notification.data;
    const statusLabels: Record<string, string> = {
      pending: 'ожидает',
      confirmed: 'подтверждён',
      processing: 'в сборке',
      ready: delivery_type === 'courier' ? 'передан курьеру' : 'готов к выдаче',
      delivering: 'в пути',
      delivered: 'доставлен',
      picked_up: 'получен',
      cancelled: 'отменён',
    };
    
    return `Заказ #${order_number} ${statusLabels[new_status] || new_status}`;
  };

  // Batch arrival message
  const getBatchArrivalMessage = (notification: BatchArrivalNotification): string => {
    const { product_count, total_quantity } = notification.data;
    return `Новая партия: ${product_count} товаров, всего ${total_quantity} ед.`;
  };

  // Stock alert message
  const getStockAlertMessage = (notification: StockAlertNotification): string => {
    const { store_name, product_name, current_stock, optimal_stock } = notification.data;
    return `${store_name}: ${product_name}. Сток: ${current_stock}/${optimal_stock}`;
  };

  // Demand alert message
  const getDemandAlertMessage = (notification: DemandAlertNotification): string => {
    const { product_name, predicted_demand, current_stock, shortage_days } = notification.data;
    return `${product_name}. Прогноз: ${predicted_demand}, сток: ${current_stock}. Закончится через ${shortage_days} дн.`;
  };

  // Mark notification as read
  const markAsRead = (notificationId: string) => {
    const notification = notifications.value.find(n => n.id === notificationId);
    if (notification && !notification.read) {
      notification.read = true;
      unreadCount.value--;
    }
  };

  // Mark all as read
  const markAllAsRead = () => {
    notifications.value.forEach(n => n.read = true);
    unreadCount.value = 0;
  };

  // Clear notifications
  const clearNotifications = () => {
    notifications.value = [];
    unreadCount.value = 0;
  };

  // Filter notifications by type
  const filterByType = (type: SupermarketNotification['type']) => {
    return notifications.value.filter(n => n.type === type);
  };

  // Filter notifications by priority
  const filterByPriority = (priority: SupermarketNotification['priority']) => {
    return notifications.value.filter(n => n.priority === priority);
  };

  // Get unread notifications
  const getUnreadNotifications = () => {
    return notifications.value.filter(n => !n.read);
  };

  // Connect to WebSocket notifications
  const connectNotifications = async () => {
    try {
      await connect('supermarket');
      isConnected.value = true;
      
      // Subscribe to supermarket channel
      send({ action: 'subscribe', channel: 'supermarket' });
      
      // Note: Message handling should be set up by the WebSocket service
      // This is a simplified version - in production, use proper event listeners
    } catch (error) {
      console.error('Failed to connect to notifications:', error);
      isConnected.value = false;
    }
  };

  // Request notification permission
  const requestNotificationPermission = async () => {
    if ('Notification' in window) {
      const permission = await Notification.requestPermission();
      if (permission === 'granted') {
        await connectNotifications();
      }
      return permission === 'granted';
    }
    return false;
  };

  // Disconnect from WebSocket
  const disconnectNotifications = () => {
    disconnect();
    isConnected.value = false;
  };

  // Lifecycle hooks
  onMounted(async () => {
    await requestNotificationPermission();
  });

  onUnmounted(() => {
    disconnectNotifications();
  });

  return {
    notifications,
    unreadCount,
    isConnected,
    markAsRead,
    markAllAsRead,
    clearNotifications,
    filterByType,
    filterByPriority,
    getUnreadNotifications,
    connectNotifications,
    disconnectNotifications,
    requestNotificationPermission,
  };
}
