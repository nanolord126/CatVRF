<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Auto\Models\AutoPart;
use Illuminate\Support\Facades\DB;

/**
 * Сервис для управления запасами запчастей.
 * Production 2026.
 */
final class AutoPartsInventoryService
{
    /**
     * Получить текущий остаток запчасти.
     */
    public function getCurrentStock(string $partId, string $correlationId = ''): int
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'getCurrentStock'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getCurrentStock', ['domain' => __CLASS__]);

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
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'reserveParts'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL reserveParts', ['domain' => __CLASS__]);

        try {
            return DB::transaction(function () use ($parts, $reason, $correlationId) {
                foreach ($parts as $partData) {
                    $part = AutoPart::query()->lockForUpdate()->find($partData['id']);

                    if (!$part || $part->current_stock < ($partData['qty'] ?? 1)) {
                        throw new \DomainException('Insufficient stock for part: ' . ($partData['id'] ?? 'unknown'));
                    }

                    $part->decrement('current_stock', $partData['qty'] ?? 1);

                    Log::channel('audit')->info('Auto parts reserved', [
                        'part_id' => $part->id,
                        'quantity' => $partData['qty'] ?? 1,
                        'reason' => $reason,
                        'correlation_id' => $correlationId,
                    ]);
                }

                return true;
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Auto parts reservation failed', [
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
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'addStock'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL addStock', ['domain' => __CLASS__]);

        try {
            return DB::transaction(function () use ($partId, $quantity, $reason, $correlationId) {
                $part = AutoPart::query()->lockForUpdate()->find($partId);

                if (!$part) {
                    throw new \DomainException('Auto part not found: ' . $partId);
                }

                $part->increment('current_stock', $quantity);

                Log::channel('audit')->info('Auto parts added', [
                    'part_id' => $part->id,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Auto parts addition failed', [
                'part_id' => $partId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }
}
