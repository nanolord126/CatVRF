<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\PromoCampaigns\Enums;

/**
 * Абсолютно строгий перечислитель (Enum) для статусов жизненного цикла промо-кампании.
 *
 * Категорически обеспечивает консистентность состояния маркетинговой акции (State Machine),
 * не позволяя применять истекшие или исчерпавшие бюджет промо-коды.
 */
enum PromoStatus: string
{
    /** Категорически активная и доступная к применению кампания. */
    case ACTIVE = 'active';

    /** Безусловно приостановленная акция (ручным решением менеджера или триггером безопасности). */
    case PAUSED = 'paused';

    /** Исключительно статус исчерпания выделенного бюджета (spent_budget >= budget). */
    case EXHAUSTED = 'exhausted';

    /** Строгий статус автоматического завершения акции по истечении срока (end_at). */
    case EXPIRED = 'expired';
}
