/**
 * Barcode Scanner WebSocket Server
 *
 * Real-time barcode scanner integration for warehouse operations
 * Handles scanner input and broadcasts updates to connected clients
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */

import { WebSocketServer } from 'ws';
import { createClient } from '@redis/client';

const WS_PORT = process.env.SCANNER_WS_PORT || 3001;
const REDIS_URL = process.env.REDIS_URL || 'redis://localhost:6379';

// Redis client for pub/sub
const redis = createClient({ url: REDIS_URL });
await redis.connect();

// WebSocket server for scanner connections
const wss = new WebSocketServer({ port: WS_PORT });

console.log(`Barcode Scanner WebSocket Server running on port ${WS_PORT}`);

// Store active scanner connections
const scanners = new Map();
const dashboardClients = new Set();

wss.on('connection', (ws, req) => {
  const url = new URL(req.url, `http://${req.headers.host}`);
  const clientType = url.searchParams.get('type') || 'unknown';
  const scannerId = url.searchParams.get('scanner_id');

  console.log(`New connection: ${clientType}`, scannerId ? `Scanner ID: ${scannerId}` : '');

  if (clientType === 'scanner') {
    // Scanner connection
    scanners.set(ws, { id: scannerId, connectedAt: new Date() });
    ws.send(JSON.stringify({ type: 'connected', scannerId }));
  } else if (clientType === 'dashboard') {
    // Dashboard client
    dashboardClients.add(ws);
    ws.send(JSON.stringify({ type: 'connected', message: 'Dashboard connected' }));
  }

  ws.on('message', async (data) => {
    try {
      const message = JSON.parse(data.toString());

      if (message.type === 'scan') {
        // Process barcode scan
        const scanResult = await processScan(message);

        // Send result back to scanner
        ws.send(JSON.stringify({
          type: 'scan_result',
          success: scanResult.success,
          data: scanResult,
        }));

        // Broadcast to dashboard clients
        broadcastToDashboards({
          type: 'scan_event',
          scannerId,
          barcode: message.barcode,
          operation: message.operation,
          timestamp: new Date().toISOString(),
          result: scanResult,
        });

        // Publish to Redis for other services
        await redis.publish('scanner:scans', JSON.stringify({
          scannerId,
          scanResult,
          timestamp: new Date().toISOString(),
        }));
      }
    } catch (error) {
      console.error('Error processing message:', error);
      ws.send(JSON.stringify({ type: 'error', message: error.message }));
    }
  });

  ws.on('close', () => {
    if (clientType === 'scanner') {
      scanners.delete(ws);
      console.log(`Scanner disconnected: ${scannerId}`);
    } else if (clientType === 'dashboard') {
      dashboardClients.delete(ws);
      console.log('Dashboard client disconnected');
    }
  });

  ws.on('error', (error) => {
    console.error('WebSocket error:', error);
  });
});

/**
 * Process barcode scan
 */
async function processScan(message) {
  const { barcode, warehouseId, operation, quantity, userId } = message;

  // Call backend API to process scan
  try {
    const response = await fetch(`${process.env.API_URL}/api/v1/scanner/scan`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${process.env.API_TOKEN}`,
      },
      body: JSON.stringify({
        barcode,
        warehouse_id: warehouseId,
        operation,
        quantity,
        user_id: userId,
      }),
    });

    const result = await response.json();
    return result;
  } catch (error) {
    return {
      success: false,
      error: error.message,
    };
  }
}

/**
 * Broadcast message to all dashboard clients
 */
function broadcastToDashboards(message) {
  const data = JSON.stringify(message);
  dashboardClients.forEach((client) => {
    if (client.readyState === 1) { // WebSocket.OPEN
      client.send(data);
    }
  });
}

// Subscribe to Redis for external scan events
const subscriber = createClient({ url: REDIS_URL });
await subscriber.connect();

await subscriber.subscribe('inventory:updates', (message) => {
  const update = JSON.parse(message);
  broadcastToDashboards({
    type: 'inventory_update',
    update,
  });
});

console.log('Barcode Scanner WebSocket Server ready');
