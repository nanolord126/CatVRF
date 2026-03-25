<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Auto\Models\AutoPart;
use Illuminate\Support\Facades\DB;

/**
 * Сервис для управления запасами запчастей.
 * Production 2026.
 */
final class AutoPartsInventoryService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    /**
     * Получить текущий остаток запчасти.
     */
    public function getCurrentStock(string $partId, string $correlationId = ''): int
    {


        $part = AutoPart::query()->find($partId);

        if (!$part) {
            throw new \DomainException('Auto part not found: ' . $partId);
        }

        return $part->current_stock;
    }

    /**
     * Зарезервировать запчасти для ремонта.
     */
    public function reserveParts(array $parts, string $reason = '', string $correlationId = ''): bool
    {


        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
$this->db->transaction(function () use ($parts, $reason, $correlationId) {
                foreach ($parts as $partData) {
                    $part = AutoPart::query()->lockForUpdate()->find($partData['id']);

                    if (!$part || $part->current_stock < ($partData['qty'] ?? 1)) {
                        throw new \DomainException('Insufficient stock for part: ' . ($partData['id'] ?? 'unknown'));
                    }

                    $part->decrement('current_stock', $partData['qty'] ?? 1);

                    $this->log->channel('audit')->info('Auto parts reserved', [
                        'part_id' => $part->id,
                        'quantity' => $partData['qty'] ?? 1,
                        'reason' => $reason,
                        'correlation_id' => $correlationId,
                    ]);
                }

                return true;
            });
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Auto parts reservation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Пополнить запас запчастей.
     */
    public function addStock(string $partId, int $quantity, string $reason = '', string $correlationId = ''): bool
    {


        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
$this->db->transaction(function () use ($partId, $quantity, $reason, $correlationId) {
                $part = AutoPart::query()->lockForUpdate()->find($partId);

                if (!$part) {
                    throw new \DomainException('Auto part not found: ' . $partId);
                }

                $part->increment('current_stock', $quantity);

                $this->log->channel('audit')->info('Auto parts added', [
                    'part_id' => $part->id,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Auto parts addition failed', [
                'part_id' => $partId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }
}
