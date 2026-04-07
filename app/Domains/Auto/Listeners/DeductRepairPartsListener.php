<?php declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class DeductRepairPartsListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    public function handle(RepairWorkCompleted $event): void
        {
            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($event) {
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

                        $this->logger->info('Auto part deducted', [
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
                $this->logger->error('DeductRepairPartsListener failed', [
                    'order_id' => $event->order->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);

                throw $e;
            }
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
