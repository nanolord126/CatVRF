<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\AppointmentCompleted;
use App\Domains\Beauty\Models\BeautyConsumable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Listener: автоматический вычет расходников при завершении записи.
 * Production 2026.
 */
final class DeductAppointmentConsumablesListener implements ShouldQueue
{
    public function handle(AppointmentCompleted $event): void
    {
        try {
            $this->db->transaction(function () use ($event) {
                $appointment = $event->appointment;
                $correlationId = $event->correlationId;

                // Получить услугу с расходниками
                $service = $appointment->service;
                if (!$service->consumables_json) {
                    return;
                }

                foreach ($service->consumables_json as $consumable) {
                    $product = \App\Domains\Beauty\Models\BeautyProduct::query()
                        ->where('id', $consumable['product_id'] ?? null)
                        ->firstOrFail();

                    // Списать расходник
                    $qty = (int) ($consumable['quantity'] ?? 1);
                    $product->decrement('current_stock', $qty);

                    // Записать в journal
                    BeautyConsumable::create([
                        'tenant_id' => $appointment->tenant_id,
                        'appointment_id' => $appointment->id,
                        'product_id' => $product->id,
                        'quantity_used' => $qty,
                        'correlation_id' => $correlationId,
                    ]);

                    // Логирование
                    $this->log->channel('audit')->info('Consumable deducted', [
                        'appointment_id' => $appointment->id,
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'correlation_id' => $correlationId,
                    ]);

                    // Проверить, не упал ли ниже минимума
                    if ($product->current_stock < $product->min_stock_threshold) {
                        event(new \App\Domains\Beauty\Events\LowStockReached($product, $correlationId));
                    }
                }
            });
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('DeductAppointmentConsumablesListener failed', [
                'appointment_id' => $event->appointment->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);

            throw $e;
        }
    }
}
