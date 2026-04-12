<?php

declare(strict_types=1);

/**
 * Class TurnoverReachedEvent
 *
 * Part of the Referral vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\Referral\Events
 */
final class TurnoverReachedEvent
{
    
    /**
     * Жестко типизированный конструктор транспортного события.
     *
     * @param int $referralId ID связи реферала.
     * @param int $recipientId ID реферера, ожидающего бонус.
     * @param string $correlationId Строгий ID трассировки.
     */
    public function __construct(
        private readonly int $referralId,
        private readonly int $recipientId,
        private readonly string $correlationId) {

    }
}
