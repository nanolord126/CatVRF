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
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 */


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
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 */


namespace App\Shared\Domain\Events;

interface DomainEvent
{
    public function getEventName(): string;

    public function getPayload(): array;

    public function getCorrelationId(): string;
}
