/**
 * WebSocket Real-time Client
 * Handles WebSocket connections for real-time updates
 */

export interface RealtimeMessage {
  type: string;
  data: Record<string, unknown>;
  timestamp: number;
}

export interface ChannelSubscription {
  channel: string;
  userId?: number;
  isPrivate: boolean;
}

export class RealtimeClient {
  private socket: WebSocket | null = null;
  private url: string;
  private token: string;
  private subscriptions: Map<string, ChannelSubscription> = new Map();
  private messageHandlers: Map<string, Function[]> = new Map();
  private reconnectAttempts = 0;
  private maxReconnectAttempts = 5;
  private reconnectDelay = 3000;
  private isIntentionallyClosed = false;

  constructor(url: string, token: string) {
    this.url = url;
    this.token = token;
  }

  /**
   * Connect to WebSocket server
   */
  public connect(): Promise<void> {
    return new Promise((resolve, reject) => {
      try {
        this.socket = new WebSocket(this.url, [this.token]);

        this.socket.onopen = () => {
          console.log('WebSocket connected');
          this.reconnectAttempts = 0;
          resolve();
        };

        this.socket.onmessage = (event) => {
          this.handleMessage(JSON.parse(event.data));
        };

        this.socket.onerror = (error) => {
          console.error('WebSocket error:', error);
          reject(error);
        };

        this.socket.onclose = () => {
          console.log('WebSocket closed');
          if (!this.isIntentionallyClosed) {
            this.attemptReconnect();
          }
        };
      } catch (error) {
        reject(error);
      }
    });
  }

  /**
   * Subscribe to channel updates
   */
  public subscribe(channel: string, isPrivate = false): void {
    const subscription: ChannelSubscription = {
      channel,
      isPrivate,
    };

    this.subscriptions.set(channel, subscription);

    this.send({
      type: 'subscribe',
      data: { channel, isPrivate },
      timestamp: Date.now(),
    });
  }

  /**
   * Unsubscribe from channel
   */
  public unsubscribe(channel: string): void {
    this.subscriptions.delete(channel);

    this.send({
      type: 'unsubscribe',
      data: { channel },
      timestamp: Date.now(),
    });
  }

  /**
   * Register message handler for event type
   */
  public on(eventType: string, handler: (data: any) => void): void {
    if (!this.messageHandlers.has(eventType)) {
      this.messageHandlers.set(eventType, []);
    }

    this.messageHandlers.get(eventType)!.push(handler);
  }

  /**
   * Remove message handler
   */
  public off(eventType: string, handler: Function): void {
    const handlers = this.messageHandlers.get(eventType);
    if (handlers) {
      const index = handlers.indexOf(handler);
      if (index > -1) {
        handlers.splice(index, 1);
      }
    }
  }

  /**
   * Send message through WebSocket
   */
  private send(message: RealtimeMessage): void {
    if (this.socket && this.socket.readyState === WebSocket.OPEN) {
      this.socket.send(JSON.stringify(message));
    }
  }

  /**
   * Handle incoming message
   */
  private handleMessage(message: RealtimeMessage): void {
    const handlers = this.messageHandlers.get(message.type) || [];

    handlers.forEach((handler) => {
      try {
        handler(message.data);
      } catch (error) {
        console.error(`Error in handler for ${message.type}:`, error);
      }
    });
  }

  /**
   * Attempt to reconnect
   */
  private attemptReconnect(): void {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++;
      setTimeout(() => {
        console.log(
          `Reconnecting... (Attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`
        );
        this.connect().catch((error) => {
          console.error('Reconnection failed:', error);
        });
      }, this.reconnectDelay * this.reconnectAttempts);
    } else {
      console.error('Max reconnection attempts reached');
    }
  }

  /**
   * Disconnect from WebSocket
   */
  public disconnect(): void {
    this.isIntentionallyClosed = true;
    if (this.socket) {
      this.socket.close();
      this.socket = null;
    }
  }

  /**
   * Get connection status
   */
  public isConnected(): boolean {
    return this.socket !== null && this.socket.readyState === WebSocket.OPEN;
  }

  /**
   * Get subscribed channels
   */
  public getSubscriptions(): string[] {
    return Array.from(this.subscriptions.keys());
  }
}
