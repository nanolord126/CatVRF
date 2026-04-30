<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CustomerMask;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CustomerAnonymizationService — Сервис анонимизации данных клиентов для CRM
 * 
 * Единое правило для всех вертикалей:
 * - Мужской пол: Котик 0000001 - Котик 9999999
 * - Женский пол: Кошечка 0000001 - Кошечка 9999999
 * - Маски контекстно-зависимые: один клиент может иметь разные маски
 *   в разных магазинах/вертикалях
 * - Клиенты не знают своих масок, только система в big data
 */
final class CustomerAnonymizationService
{
    private const MAX_MASK_NUMBER = 9999999;
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Получить или создать маску для клиента в контексте
     * 
     * @param int $customerId Реальный ID клиента
     * @param int $tenantId ID тенанта
     * @param int|null $verticalId ID вертикали (для контекстных масок)
     * @param int|null $storeId ID магазина (для контекстных масок)
     * @return string Маска в формате "Котик 0000001" или "Кошечка 0000001"
     */
    public function getOrCreateMask(
        int $customerId,
        int $tenantId,
        ?int $verticalId = null,
        ?int $storeId = null
    ): string {
        $cacheKey = $this->getCacheKey($customerId, $tenantId, $verticalId, $storeId);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use (
            $customerId,
            $tenantId,
            $verticalId,
            $storeId
        ) {
            // Try to find existing active mask
            $mask = CustomerMask::active()
                ->forContext($tenantId, $verticalId, $storeId)
                ->where('customer_id', $customerId)
                ->first();

            if ($mask) {
                return $mask->full_mask;
            }

            // Create new mask
            return $this->createMask($customerId, $tenantId, $verticalId, $storeId);
        });
    }

    /**
     * Получить маску без создания (только чтение)
     */
    public function getMask(
        int $customerId,
        int $tenantId,
        ?int $verticalId = null,
        ?int $storeId = null
    ): ?string {
        $cacheKey = $this->getCacheKey($customerId, $tenantId, $verticalId, $storeId);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use (
            $customerId,
            $tenantId,
            $verticalId,
            $storeId
        ) {
            $mask = CustomerMask::active()
                ->forContext($tenantId, $verticalId, $storeId)
                ->where('customer_id', $customerId)
                ->first();

            return $mask?->full_mask;
        });
    }

    /**
     * Анонимизировать данные клиента для CRM
     * 
     * @param array $customerData Исходные данные клиента
     * @param int $tenantId ID тенанта
     * @param int|null $verticalId ID вертикали
     * @param int|null $storeId ID магазина
     * @return array Анонимизированные данные
     */
    public function anonymizeCustomerData(
        array $customerData,
        int $tenantId,
        ?int $verticalId = null,
        ?int $storeId = null
    ): array {
        if (!isset($customerData['id'])) {
            return $customerData;
        }

        $mask = $this->getOrCreateMask(
            (int)$customerData['id'],
            $tenantId,
            $verticalId,
            $storeId
        );

        // Remove PII
        $anonymized = array_diff_key($customerData, array_flip([
            'first_name',
            'last_name',
            'middle_name',
            'email',
            'phone',
            'passport_number',
            'passport_series',
            'inn',
            'snils',
            'address',
            'birth_date',
        ]));

        // Add anonymized data
        $anonymized['masked_id'] = $mask;
        $anonymized['gender'] = $customerData['gender'] ?? 'unknown';
        $anonymized['age_group'] = $this->getAgeGroup($customerData['birth_date'] ?? null);
        $anonymized['is_anonymized'] = true;

        return $anonymized;
    }

    /**
     * Создать новую маску для клиента
     */
    private function createMask(
        int $customerId,
        int $tenantId,
        ?int $verticalId,
        ?int $storeId
    ): string {
        return DB::transaction(function () use (
            $customerId,
            $tenantId,
            $verticalId,
            $storeId
        ) {
            // Get customer gender
            $customer = User::find($customerId);
            $maskType = $this->determineMaskType($customer);

            // Generate unique mask number
            $maskNumber = $this->generateUniqueMaskNumber($tenantId, $verticalId, $storeId);

            $fullMask = $this->formatMask($maskType, $maskNumber);

            CustomerMask::create([
                'customer_id' => $customerId,
                'tenant_id' => $tenantId,
                'vertical_id' => $verticalId,
                'store_id' => $storeId,
                'mask_type' => $maskType,
                'mask_number' => $maskNumber,
                'full_mask' => $fullMask,
                'is_active' => true,
                'initialized_at' => now(),
            ]);

            Log::info('Customer mask created', [
                'customer_id' => $customerId,
                'tenant_id' => $tenantId,
                'vertical_id' => $verticalId,
                'store_id' => $storeId,
                'mask' => $fullMask,
            ]);

            return $fullMask;
        });
    }

    /**
     * Определить тип маски по полу клиента
     */
    private function determineMaskType(?User $customer): string
    {
        if (!$customer) {
            return CustomerMask::MASK_TYPE_CAT; // Default
        }

        $gender = $customer->gender ?? 'unknown';

        return match($gender) {
            'male' => CustomerMask::MASK_TYPE_CAT,
            'female' => CustomerMask::MASK_TYPE_KITTEN,
            default => CustomerMask::MASK_TYPE_CAT, // Default for unknown
        };
    }

    /**
     * Сгенерировать уникальный номер маски
     */
    private function generateUniqueMaskNumber(
        int $tenantId,
        ?int $verticalId,
        ?int $storeId
    ): string {
        $maxAttempts = 100;
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $number = str_pad((string)rand(1, self::MAX_MASK_NUMBER), 7, '0', STR_PAD_LEFT);
            
            $exists = CustomerMask::where('mask_number', $number)
                ->forContext($tenantId, $verticalId, $storeId)
                ->exists();

            if (!$exists) {
                return $number;
            }

            $attempts++;
        }

        // Fallback: use timestamp-based number
        return substr((string)now()->timestamp, -7);
    }

    /**
     * Форматировать маску
     */
    private function formatMask(string $maskType, string $maskNumber): string
    {
        $label = match($maskType) {
            CustomerMask::MASK_TYPE_CAT => 'Котик',
            CustomerMask::MASK_TYPE_KITTEN => 'Кошечка',
        };

        return "{$label} {$maskNumber}";
    }

    /**
     * Получить возрастную группу
     */
    private function getAgeGroup(?string $birthDate): string
    {
        if (!$birthDate) {
            return 'unknown';
        }

        try {
            $age = now()->diffInYears(\Carbon\Carbon::parse($birthDate));

            return match(true) {
                $age < 18 => 'minor',
                $age < 25 => 'young_adult',
                $age < 45 => 'adult',
                $age < 65 => 'middle_aged',
                default => 'senior',
            };
        } catch (\Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Получить ключ кэша
     */
    private function getCacheKey(
        int $customerId,
        int $tenantId,
        ?int $verticalId,
        ?int $storeId
    ): string {
        return "customer_mask:{$customerId}:{$tenantId}:{$verticalId}:{$storeId}";
    }

    /**
     * Очистить кэш маски клиента
     */
    public function clearMaskCache(
        int $customerId,
        int $tenantId,
        ?int $verticalId = null,
        ?int $storeId = null
    ): void {
        $cacheKey = $this->getCacheKey($customerId, $tenantId, $verticalId, $storeId);
        Cache::forget($cacheKey);
    }

    /**
     * Деактивировать маску
     */
    public function deactivateMask(int $maskId): bool
    {
        $mask = CustomerMask::find($maskId);
        if (!$mask) {
            return false;
        }

        $mask->update(['is_active' => false]);
        $this->clearMaskCache(
            $mask->customer_id,
            $mask->tenant_id,
            $mask->vertical_id,
            $mask->store_id
        );

        return true;
    }
}
