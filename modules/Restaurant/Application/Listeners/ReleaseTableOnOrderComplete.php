<?php

declare(strict_types=1);

namespace Modules\Restaurant\Listeners;

use Modules\Restaurant\Events\OrderStatusChanged;
use Modules\Restaurant\Enums\OrderStatus;
use Modules\Restaurant\Models\Table;
use Illuminate\Log\LogManager;

/**
 * Release Table On Order Complete — Автоматическое освобождение столика
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 */
final readonly class ReleaseTableOnOrderComplete
{
    public function handle(OrderStatusChanged $event, LogManager $log): void
    {
        // Освобождаем столик только при завершении или отмене
        if (!in_array($event->newStatus, [OrderStatus::COMPLETED, OrderStatus::CANCELLED], true)) {
            return;
        }

        $order = $event->order;

        if (!$order->table_id) {
            return;
        }

        try {
            $table = Table::find($order->table_id);
            if ($table && $table->canBeReleased()) {
                $table->release();

                $log->channel('restaurant')->info('Table released', [
                    'order_id' => $order->id,
                    'table_id' => $table->id,
                    'table_number' => $table->table_number,
                    'correlation_id' => $event->correlationId,
                ]);
            }
        } catch (\Exception $e) {
            $log->channel('restaurant')->error('Failed to release table', [
                'order_id' => $order->id,
                'table_id' => $order->table_id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
        }
    }
}
