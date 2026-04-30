<?php

declare(strict_types=1);

use App\Models\Event;
use Illuminate\Support\Facades\Broadcast;
use App\Domains\Food\Models\DeliveryOrder;
use App\Domains\Logistics\Models\Courier;
use App\Models\Order;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Live Stream WebRTC Mesh Channel
 * Multi-tenant aware broadcast channel for P2P peer connections
 */
Broadcast::channel('stream.{streamId}', function ($user, $streamId) {
    $stream = Event::find($streamId);

    if (! $stream) {
        return false;
    }

    // Verify tenant isolation (critical for security)
    if ($user->tenant_id !== $stream->tenant_id) {
        return false;
    }

    // Optional: Check if user has access to this stream
    // (can be further restricted based on ticket status, permissions, etc.)
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar_url,
    ];
});

/**
 * Private user-specific channel for notifications
 */
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

/**
 * Delivery tracking channel (Food vertical)
 * delivery.{deliveryId} - real-time delivery updates
 */
Broadcast::channel('delivery.{deliveryId}', function ($user, $deliveryId) {
    // Check if user has access to this delivery
    $delivery = DeliveryOrder::find($deliveryId);
    if (! $delivery) {
        return false;
    }

    // Tenant isolation
    $tenantId = function_exists('tenant') && tenant() ? tenant()->id : $user->tenant_id;
    if ($delivery->tenant_id !== $tenantId) {
        return false;
    }

    // User is the customer or courier
    if ($delivery->customer_id === $user->id || $delivery->courier_id === $user->id) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $delivery->courier_id === $user->id ? 'courier' : 'customer',
        ];
    }

    return false;
});

/**
 * Courier location tracking channel
 * courier.{courierId}.location - real-time GPS updates
 */
Broadcast::channel('courier.{courierId}.location', function ($user, $courierId) {
    // Only courier themselves or admin can track
    if ((int) $user->id === (int) $courierId || $user->isAdmin()) {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    return false;
});

/**
 * Tenant couriers channel
 * tenant.{tenantId}.couriers - all active couriers for tenant
 */
Broadcast::channel('tenant.{tenantId}.couriers', function ($user, $tenantId) {
    // Tenant isolation
    $userTenantId = function_exists('tenant') && tenant() ? tenant()->id : $user->tenant_id;
    if ((int) $userTenantId !== (int) $tenantId) {
        return false;
    }

    // Only tenant members/admins can see all couriers
    return [
        'id' => $user->id,
        'name' => $user->name,
        'tenant_id' => $tenantId,
    ];
});

/**
 * Unified Logistics Core - Order tracking channel
 * orders.{orderId} - real-time order fulfillment updates
 */
Broadcast::channel('orders.{orderId}', function ($user, $orderId) {
    $order = Order::find($orderId);
    if (! $order) {
        return false;
    }

    // Tenant isolation
    $tenantId = function_exists('tenant') && tenant() ? tenant()->id : $user->tenant_id;
    if ($order->tenant_id !== $tenantId) {
        return false;
    }

    // User is the order owner or admin
    if ($order->user_id === $user->id || $user->isAdmin()) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $order->user_id === $user->id ? 'customer' : 'admin',
        ];
    }

    return false;
});

/**
 * Unified Logistics Core - Courier channel
 * couriers.{courierId} - courier status and location updates
 */
Broadcast::channel('couriers.{courierId}', function ($user, $courierId) {
    $courier = Courier::find($courierId);
    if (! $courier) {
        return false;
    }

    // Tenant isolation
    $tenantId = function_exists('tenant') && tenant() ? tenant()->id : $user->tenant_id;
    if ($courier->tenant_id !== $tenantId) {
        return false;
    }

    // Courier themselves or admin can access
    if ($courier->user_id === $user->id || $user->isAdmin()) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $courier->user_id === $user->id ? 'courier' : 'admin',
        ];
    }

    return false;
});

/**
 * Unified Logistics Core - User logistics channel
 * users.{userId} - personal logistics notifications
 */
Broadcast::channel('users.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

/**
 * WMS Barcode Scanner Channel
 * scanner.{tenantId}.{warehouseId} - real-time barcode scan updates
 * Multi-tenant and warehouse-aware for inventory operations
 */
Broadcast::channel('scanner.{tenantId}.{warehouseId}', function ($user, $tenantId, $warehouseId) {
    // Tenant isolation (critical for security)
    $userTenantId = function_exists('tenant') && tenant() ? tenant()->id : $user->tenant_id;
    if ((int) $userTenantId !== (int) $tenantId) {
        return false;
    }

    // Check if user has access to this warehouse
    $hasWarehouseAccess = \Illuminate\Support\Facades\DB::table('warehouse_users')
        ->where('warehouse_id', $warehouseId)
        ->where('user_id', $user->id)
        ->exists();

    // Admin users or warehouse staff can access
    if ($user->isAdmin() || $hasWarehouseAccess) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'role' => $user->isAdmin() ? 'admin' : 'staff',
        ];
    }

    return false;
});
