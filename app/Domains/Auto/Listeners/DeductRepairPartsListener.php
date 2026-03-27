<?php

declare(strict_types=1);


namespace App\Domains\Auto\Listeners;

use App\Domains\Auto\Events\RepairWorkCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Listener: автоматический вычет запчастей при завершении ремонта.
 * Production 2026.
 */
final class DeductRepairPartsListener implements ShouldQueue
{
    public function handle(RepairWorkCompleted $event): void
    {
        try {
            DB::transaction(function () use ($event) {
                $order = $event->order;
                $service = $order->service;
                $correlationId = $event->correlationId;

                if (!$service || !$service->required_parts) {
                    return;
                }

                foreach ($service->required_parts as $part) {
                    $partModel = \App\Domains\Auto\Models\AutoPart::query()
                        ->where('id', $part['id'] ?? null)
                        ->firstOrFail();

                    $qty = (int) ($part['qty'] ?? 1);
                    $partModel->decrement('current_stock', $qty);

                    Log::channel('audit')->info('Auto part deducted', [
                        'order_id' => $order->id,
                        'part_id' => $partModel->id,
                        'quantity' => $qty,
                        'correlation_id' => $correlationId,
                    ]);

                    if ($partModel->current_stock < $partModel->min_stock_threshold) {
                        event(new \App\Domains\Auto\Events\LowPartsStock($partModel, $correlationId));
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('DeductRepairPartsListener failed', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);

            throw $e;
        }
    }
}
