<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SupplierDelayRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PaymentDelayValidationService — Валидация отсрочек платежа для поставщиков
 * 
 * Правила:
 * - Поставщики могут инициализировать отсрочку до 14 дней самостоятельно
 * - Отсрочка более 14 дней только через систему по запросу в поддержку
 * - Только для конкретного ИНН
 * - Требует подтверждения администратором для больших отсрочек
 */
final class PaymentDelayValidationService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Проверить, может ли поставщик инициализировать отсрочку
     * 
     * @param int $supplierId ID поставщика
     * @param int $tenantId ID тенанта
     * @param string $inn ИНН
     * @param int $delayDays Запрошенное количество дней отсрочки
     * @return array ['allowed' => bool, 'reason' => string|null]
     */
    public function canInitiateDelay(
        int $supplierId,
        int $tenantId,
        string $inn,
        int $delayDays
    ): array {
        // Проверка на максимальную самостоятельную отсрочку
        if ($delayDays > SupplierDelayRequest::MAX_SELF_INITIATED_DELAY_DAYS) {
            return [
                'allowed' => false,
                'reason' => 'Delay exceeds self-initiated limit. Maximum self-initiated delay is 14 days. For longer delays, please submit a support request.',
                'requires_support_request' => true,
            ];
        }

        // Проверка на отрицательное значение
        if ($delayDays <= 0) {
            return [
                'allowed' => false,
                'reason' => 'Delay days must be greater than 0',
                'requires_support_request' => false,
            ];
        }

        // Проверка на уже существующую действующую отсрочку для этого ИНН
        $existingDelay = $this->getExistingValidDelay($tenantId, $inn);
        if ($existingDelay) {
            return [
                'allowed' => false,
                'reason' => 'A valid delay already exists for this INN. Please use the existing delay or request an extension through support.',
                'existing_delay' => $existingDelay,
                'requires_support_request' => true,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'requires_support_request' => false,
        ];
    }

    /**
     * Создать запрос на отсрочку (для больших отсрочек)
     * 
     * @param int $supplierId ID поставщика
     * @param int $tenantId ID тенанта
     * @param string $inn ИНН
     * @param int $delayDays Запрошенное количество дней
     * @param int $requestedByUserId ID пользователя, делающего запрос
     * @param string|null $reason Причина
     * @return SupplierDelayRequest
     */
    public function createDelayRequest(
        int $supplierId,
        int $tenantId,
        string $inn,
        int $delayDays,
        int $requestedByUserId,
        ?string $reason = null
    ): SupplierDelayRequest {
        $request = SupplierDelayRequest::create([
            'supplier_id' => $supplierId,
            'tenant_id' => $tenantId,
            'inn' => $inn,
            'requested_by_user_id' => $requestedByUserId,
            'requested_delay_days' => $delayDays,
            'reason' => $reason,
            'status' => SupplierDelayRequest::STATUS_PENDING,
        ]);

        Log::info('Payment delay request created', [
            'request_id' => $request->id,
            'supplier_id' => $supplierId,
            'tenant_id' => $tenantId,
            'inn' => $inn,
            'delay_days' => $delayDays,
            'exceeds_limit' => $request->exceedsSelfInitiatedLimit(),
        ]);

        return $request;
    }

    /**
     * Одобрить запрос на отсрочку (админ)
     * 
     * @param int $requestId ID запроса
     * @param int $approvedByUserId ID администратора
     * @param string|null $adminNotes Заметки администратора
     * @param int|null $validityDays Срок действия одобрения (null = permanent)
     * @return bool
     */
    public function approveDelayRequest(
        int $requestId,
        int $approvedByUserId,
        ?string $adminNotes = null,
        ?int $validityDays = null
    ): bool {
        $request = SupplierDelayRequest::findOrFail($requestId);

        if ($request->status !== SupplierDelayRequest::STATUS_PENDING) {
            Log::warning('Cannot approve non-pending delay request', [
                'request_id' => $requestId,
                'current_status' => $request->status,
            ]);
            return false;
        }

        $expiresAt = $validityDays ? now()->addDays($validityDays) : null;

        $request->update([
            'status' => SupplierDelayRequest::STATUS_APPROVED,
            'approved_by_user_id' => $approvedByUserId,
            'admin_notes' => $adminNotes,
            'approved_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        // Clear cache for this INN
        $this->clearDelayCache($request->tenant_id, $request->inn);

        Log::info('Payment delay request approved', [
            'request_id' => $requestId,
            'approved_by' => $approvedByUserId,
            'delay_days' => $request->requested_delay_days,
            'expires_at' => $expiresAt?->toIso8601String(),
        ]);

        return true;
    }

    /**
     * Отклонить запрос на отсрочку (админ)
     * 
     * @param int $requestId ID запроса
     * @param int $rejectedByUserId ID администратора
     * @param string|null $adminNotes Заметки администратора
     * @return bool
     */
    public function rejectDelayRequest(
        int $requestId,
        int $rejectedByUserId,
        ?string $adminNotes = null
    ): bool {
        $request = SupplierDelayRequest::findOrFail($requestId);

        if ($request->status !== SupplierDelayRequest::STATUS_PENDING) {
            return false;
        }

        $request->update([
            'status' => SupplierDelayRequest::STATUS_REJECTED,
            'approved_by_user_id' => $rejectedByUserId,
            'admin_notes' => $adminNotes,
        ]);

        Log::info('Payment delay request rejected', [
            'request_id' => $requestId,
            'rejected_by' => $rejectedByUserId,
            'reason' => $adminNotes,
        ]);

        return true;
    }

    /**
     * Получить существующую действующую отсрочку
     * 
     * @param int $tenantId ID тенанта
     * @param string $inn ИНН
     * @return SupplierDelayRequest|null
     */
    public function getExistingValidDelay(int $tenantId, string $inn): ?SupplierDelayRequest
    {
        $cacheKey = "payment_delay:{$tenantId}:{$inn}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId, $inn) {
            return SupplierDelayRequest::valid()
                ->where('tenant_id', $tenantId)
                ->where('inn', $inn)
                ->orderBy('requested_delay_days', 'desc')
                ->first();
        });
    }

    /**
     * Проверить, есть ли действующая отсрочка для ИНН
     * 
     * @param int $tenantId ID тенанта
     * @param string $inn ИНН
     * @return bool
     */
    public function hasValidDelay(int $tenantId, string $inn): bool
    {
        return $this->getExistingValidDelay($tenantId, $inn) !== null;
    }

    /**
     * Получить максимальную разрешенную отсрочку для ИНН
     * 
     * @param int $tenantId ID тенанта
     * @param string $inn ИНН
     * @return int Количество дней (0 если нет отсрочки)
     */
    public function getMaxAllowedDelayDays(int $tenantId, string $inn): int
    {
        $delay = $this->getExistingValidDelay($tenantId, $inn);
        
        return $delay ? $delay->requested_delay_days : 0;
    }

    /**
     * Очистить кэш отсрочки
     */
    private function clearDelayCache(int $tenantId, string $inn): void
    {
        $cacheKey = "payment_delay:{$tenantId}:{$inn}";
        Cache::forget($cacheKey);
    }

    /**
     * Получить статистику запросов на отсрочку
     * 
     * @param int|null $tenantId ID тенанта (null = all)
     * @return array
     */
    public function getDelayRequestStatistics(?int $tenantId = null): array
    {
        $query = SupplierDelayRequest::query();
        
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return [
            'total' => $query->count(),
            'pending' => (clone $query)->pending()->count(),
            'approved' => (clone $query)->approved()->count(),
            'rejected' => (clone $query)->where('status', SupplierDelayRequest::STATUS_REJECTED)->count(),
            'cancelled' => (clone $query)->where('status', SupplierDelayRequest::STATUS_CANCELLED)->count(),
            'valid_now' => (clone $query)->valid()->count(),
            'exceeding_limit' => (clone $query)->where('requested_delay_days', '>', SupplierDelayRequest::MAX_SELF_INITIATED_DELAY_DAYS)->count(),
        ];
    }
}
