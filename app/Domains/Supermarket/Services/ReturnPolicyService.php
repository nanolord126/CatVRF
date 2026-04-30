<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Services;

use App\Domains\Supermarket\DTOs\CreateReturnData;
use App\Domains\Supermarket\Enums\ReturnCondition;
use App\Domains\Supermarket\Enums\ReturnReason;
use App\Domains\Supermarket\Models\Return as ReturnModel;
use App\Domains\Supermarket\Models\ReturnItem;
use App\Domains\Supermarket\Models\ReturnPolicy;
use App\Domains\Supermarket\Models\SupermarketOrder;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * ReturnPolicyService - Сервис правил возврата для Supermarket.
 *
 * Определяет политики возврата в зависимости от:
 * - Под-вертикали (grocery_and_delivery, food, confectionery, etc.)
 * - Типа товара (скоропортящиеся, обычные)
 * - Холодной цепи
 * - Причины возврата
 *
 * Правила по умолчанию:
 * - Скоропортящиеся товары (мясо, молочка, готовка) → возврат только при браке, в течение 24 часов
 * - Остальные товары → 7 дней
 * - Холодная цепь → возврат только с фото + чек температуры
 * - Изменение мнения → возврат только для обычных товаров, не вскрытых
 */
final readonly class ReturnPolicyService
{
    use WithAuditLogging;

    private const CACHE_TTL = 3600; // 1 час

    /**
     * @param AuditService $audit Сервис аудита
     * @param LoggerInterface $logger Логгер
     */
    public function __construct(
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Проверить, можно ли оформить возврат для заказа.
     *
     * @param SupermarketOrder $order Заказ
     * @param ReturnReason $reason Причина возврата
     * @param bool $isColdChain Была ли холодная цепь
     * @return array{allowed: bool, reason: string, max_hours: int|null}
     */
    public function canCreateReturn(SupermarketOrder $order, ReturnReason $reason, bool $isColdChain): array
    {
        $cacheKey = "return_policy:{$order->id}:{$reason->value}:{$isColdChain}";
        
        return Cache::tags(['return_policy', "order:{$order->id}"])->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($order, $reason, $isColdChain) {
                $hoursSinceOrder = $order->created_at->diffInHours(now());
                $subVertical = $order->sub_vertical ?? 'grocery_and_delivery';

                $this->logger->info('Checking return policy', [
                    'order_id' => $order->id,
                    'sub_vertical' => $subVertical,
                    'reason' => $reason->value,
                    'is_cold_chain' => $isColdChain,
                    'hours_since_order' => $hoursSinceOrder,
                ]);

                // Правила для скоропортящихся товаров с холодной цепью
                if ($isColdChain) {
                    if ($hoursSinceOrder > 24) {
                        return [
                            'allowed' => false,
                            'reason' => 'Для товаров с холодной цепью возврат возможен в течение 24 часов',
                            'max_hours' => 24,
                        ];
                    }

                    if ($reason === ReturnReason::CHANGED_MIND) {
                        return [
                            'allowed' => false,
                            'reason' => 'Для товаров с холодной цепью возврат по причине "изменил мнение" не допускается',
                            'max_hours' => null,
                        ];
                    }

                    return [
                        'allowed' => true,
                        'reason' => 'Возврат разрешен с фото доказательствами',
                        'max_hours' => 24,
                    ];
                }

                // Правила по под-вертикалям
                return match ($subVertical) {
                    'food', 'confectionery', 'bakery' => $this->checkPerishablePolicy($hoursSinceOrder, $reason),
                    'grocery_and_delivery', 'household' => $this->checkStandardPolicy($hoursSinceOrder, $reason),
                    default => $this->checkStandardPolicy($hoursSinceOrder, $reason),
                };
            }
        );
    }

    /**
     * Проверить политику для скоропортящихся товаров.
     *
     * @param int $hoursSinceOrder Часов с момента заказа
     * @param ReturnReason $reason Причина возврата
     * @return array{allowed: bool, reason: string, max_hours: int|null}
     */
    private function checkPerishablePolicy(int $hoursSinceOrder, ReturnReason $reason): array
    {
        $maxHours = 24;

        if ($hoursSinceOrder > $maxHours) {
            return [
                'allowed' => false,
                'reason' => 'Для скоропортящихся товаров возврат возможен в течение 24 часов',
                'max_hours' => $maxHours,
            ];
        }

        if ($reason === ReturnReason::CHANGED_MIND) {
            return [
                'allowed' => false,
                'reason' => 'Для скоропортящихся товаров возврат по причине "изменил мнение" не допускается',
                'max_hours' => null,
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'Возврат разрешен при наличии брака',
            'max_hours' => $maxHours,
        ];
    }

    /**
     * Проверить стандартную политику возврата.
     *
     * @param int $hoursSinceOrder Часов с момента заказа
     * @param ReturnReason $reason Причина возврата
     * @return array{allowed: bool, reason: string, max_hours: int|null}
     */
    private function checkStandardPolicy(int $hoursSinceOrder, ReturnReason $reason): array
    {
        $maxHours = 168; // 7 дней = 168 часов

        if ($hoursSinceOrder > $maxHours) {
            return [
                'allowed' => false,
                'reason' => 'Срок возврата истек (7 дней)',
                'max_hours' => $maxHours,
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'Возврат разрешен',
            'max_hours' => $maxHours,
        ];
    }

    /**
     * Проверить, требуются ли фото доказательства.
     *
     * @param bool $isColdChain Была ли холодная цепь
     * @param ReturnReason $reason Причина возврата
     * @return bool
     */
    public function requiresPhotoEvidence(bool $isColdChain, ReturnReason $reason): bool
    {
        if ($isColdChain) {
            return true;
        }

        return match ($reason) {
            ReturnReason::SPOILED,
            ReturnReason::DAMAGED,
            ReturnReason::EXPIRED => true,
            default => false,
        };
    }

    /**
     * Получить приоритет возврата.
     *
     * @param bool $isColdChain Была ли холодная цепь
     * @param ReturnReason $reason Причина возврата
     * @return string
     */
    public function getReturnPriority(bool $isColdChain, ReturnReason $reason): string
    {
        if ($isColdChain) {
            return 'urgent';
        }

        return match ($reason) {
            ReturnReason::SPOILED,
            ReturnReason::DAMAGED,
            ReturnReason::EXPIRED => 'high',
            default => 'normal',
        };
    }

    /**
     * Проверить, можно ли вернуть товар без фото.
     *
     * @param bool $isColdChain Была ли холодная цепь
     * @param ReturnReason $reason Причина возврата
     * @return bool
     */
    public function canReturnWithoutPhoto(bool $isColdChain, ReturnReason $reason): bool
    {
        return !$this->requiresPhotoEvidence($isColdChain, $reason);
    }

    /**
     * Получить максимальный срок возврата в часах для под-вертикали.
     *
     * @param string|null $subVertical Под-вертикаль
     * @return int
     */
    public function getMaxReturnHours(?string $subVertical): int
    {
        return match ($subVertical) {
            'food', 'confectionery', 'bakery' => 24,
            default => 168, // 7 дней
        };
    }

    /**
     * Очистить кэш политики для заказа.
     *
     * @param int $orderId ID заказа
     * @return void
     */
    public function clearPolicyCache(int $orderId): void
    {
        Cache::tags(['return_policy', "order:{$orderId}"])->flush();
        
        $this->logger->info('Return policy cache cleared', [
            'order_id' => $orderId,
        ]);
    }

    /**
     * Валидировать товар возврата с политикой (для автоматической проверки).
     *
     * @param ReturnItem $item Товар в возврате
     * @param CreateReturnData $returnData Данные возврата
     * @param ReturnPolicy $policy Политика
     * @return array{valid: bool, reasons: array}
     */
    public function validateReturnItem(
        ReturnItem $item,
        CreateReturnData $returnData,
        ReturnPolicy $policy
    ): array {
        $reasons = [];

        // 1. Проверка срока
        $daysPassed = now()->diffInDays($returnData->order->created_at);
        if ($daysPassed > $policy->max_days) {
            $reasons[] = "Превышен срок возврата ({$policy->max_days} дней)";
        }

        // 2. Разрешённая причина
        if (!in_array($returnData->reasonType->value, $policy->allowed_reasons)) {
            $reasons[] = "Причина возврата не разрешена для этой категории";
        }

        // 3. Холодная цепь — только брак
        if ($policy->cold_chain_only_defect && $returnData->isColdChain) {
            if ($returnData->reasonType->value !== 'spoiled') {
                $reasons[] = "Товары с холодной цепью принимаются только при браке";
            }
        }

        // 4. Обязательные доказательства
        if ($policy->requires_photo && !$returnData->hasImages()) {
            $reasons[] = "Требуются фото для подтверждения";
        }

        // 5. Состояние товара для холодной цепи
        if ($returnData->isColdChain && $item->isOpened()) {
            $reasons[] = "Вскрытые товары с холодной цепью к возврату не принимаются";
        }

        return [
            'valid' => empty($reasons),
            'reasons' => $reasons,
        ];
    }

    /**
     * Проверить, требуются ли фото доказательства.
     *
     * @param bool $isColdChain Была ли холодная цепь
     * @param ReturnReason $reason Причина возврата
     * @param string|null $subVertical Под-вертикаль
     * @param string $customerType Тип клиента
     * @return bool
     */
    public function requiresPhotoEvidence(
        bool $isColdChain,
        ReturnReason $reason,
        ?string $subVertical = null,
        string $customerType = 'b2c'
    ): bool {
        $policy = $this->getPolicy('supermarket', $subVertical, $customerType);

        if ($policy->requires_photo) {
            return true;
        }

        if ($isColdChain) {
            return true;
        }

        return match ($reason) {
            ReturnReason::SPOILED,
            ReturnReason::DAMAGED,
            ReturnReason::EXPIRED => true,
            default => false,
        };
    }

    /**
     * Получить приоритет возврата.
     *
     * @param bool $isColdChain Была ли холодная цепь
     * @param ReturnReason $reason Причина возврата
     * @param string|null $subVertical Под-вертикаль
     * @return string
     */
    public function getReturnPriority(
        bool $isColdChain,
        ReturnReason $reason,
        ?string $subVertical = null
    ): string {
        $policy = $this->getPolicy('supermarket', $subVertical, 'b2c');

        if ($isColdChain || $policy->cold_chain_only_defect) {
            return 'urgent';
        }

        return match ($reason) {
            ReturnReason::SPOILED,
            ReturnReason::DAMAGED,
            ReturnReason::EXPIRED => 'high',
            default => 'normal',
        };
    }

    /**
     * Проверить, можно ли вернуть товар без фото.
     *
     * @param bool $isColdChain Была ли холодная цепь
     * @param ReturnReason $reason Причина возврата
     * @param string|null $subVertical Под-вертикаль
     * @param string $customerType Тип клиента
     * @return bool
     */
    public function canReturnWithoutPhoto(
        bool $isColdChain,
        ReturnReason $reason,
        ?string $subVertical = null,
        string $customerType = 'b2c'
    ): bool {
        return !$this->requiresPhotoEvidence($isColdChain, $reason, $subVertical, $customerType);
    }

    /**
     * Получить максимальный срок возврата в часах для под-вертикали.
     *
     * @param string|null $subVertical Под-вертикаль
     * @param string $customerType Тип клиента
     * @return int
     */
    public function getMaxReturnHours(?string $subVertical, string $customerType = 'b2c'): int
    {
        $policy = $this->getPolicy('supermarket', $subVertical, $customerType);
        return $policy->max_days * 24;
    }
}
