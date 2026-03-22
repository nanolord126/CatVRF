<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Auto\Models\AutoPart;
use Illuminate\Support\Facades\DB;

final class AutoInventoryService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,)
    {
    }

    /**
     * Зарезервировать запчасти при создании заказа на ремонт
     */
    public function reserveParts(int $orderId, array $parts, string $correlationId): bool
    {
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );

        if ($fraudResult['decision'] === 'block') {
            Log::channel('fraud_alert')->warning('Parts reservation blocked', [
                'correlation_id' => $correlationId,
                'order_id' => $orderId,
                'score' => $fraudResult['score'],
            ]);
            throw new \Exception('Operation blocked by fraud control');
        }

        try {
                foreach ($parts as $partId => $quantity) {
                    $part = AutoPart::lockForUpdate()->findOrFail($partId);

                    if ($part->current_stock < $quantity) {
                        throw new \Exception("Insufficient stock for part {$partId}");
                    }

                    $part->increment('hold_stock', $quantity);

                    Log::channel('audit')->info('Parts reserved', [
                        'order_id' => $orderId,
                        'part_id' => $partId,
                        'quantity' => $quantity,
                        'correlation_id' => $correlationId,
                    ]);
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Parts reservation failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Списать запчасти после завершения ремонта
     */
    public function deductParts(int $orderId, array $parts, string $correlationId): bool
    {
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );

        if ($fraudResult['decision'] === 'block') {
            Log::channel('fraud_alert')->warning('Parts deduction blocked', [
                'correlation_id' => $correlationId,
                'order_id' => $orderId,
                'score' => $fraudResult['score'],
            ]);
            throw new \Exception('Operation blocked by fraud control');
        }

        try {
            DB::transaction(function () use ($orderId, $parts, $correlationId) {
                foreach ($parts as $partId => $quantity) {
                    $part = AutoPart::lockForUpdate()->findOrFail($partId);

                    $part->decrement('current_stock', $quantity);
                    $part->decrement('hold_stock', $quantity);

                    Log::channel('audit')->info('Parts deducted', [
                        'order_id' => $orderId,
                        'part_id' => $partId,
                        'quantity' => $quantity,
                        'remaining_stock' => $part->current_stock,
                        'correlation_id' => $correlationId,
                    ]);
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Parts deduction failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Отпустить зарезервированные запчасти при отмене
     */
    public function releaseParts(int $orderId, array $parts, string $correlationId): bool
    {
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );

        if ($fraudResult['decision'] === 'block') {
            Log::channel('fraud_alert')->warning('Parts release blocked', [
                'correlation_id' => $correlationId,
                'order_id' => $orderId,
                'score' => $fraudResult['score'],
            ]);
            throw new \Exception('Operation blocked by fraud control');
        }

        try {
            DB::transaction(function () use ($orderId, $parts, $correlationId) {
                foreach ($parts as $partId => $quantity) {
                    $part = AutoPart::lockForUpdate()->findOrFail($partId);
                    $part->decrement('hold_stock', $quantity);

                    Log::channel('audit')->info('Parts released', [
                        'order_id' => $orderId,
                        'part_id' => $partId,
                        'quantity' => $quantity,
                        'correlation_id' => $correlationId,
                    ]);
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Parts release failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
