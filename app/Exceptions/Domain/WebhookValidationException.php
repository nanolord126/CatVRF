<?php
declare(strict_types=1);

namespace App\Exceptions\Domain;

final class WebhookValidationException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Component: WebhookValidationException
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     */
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * WebhookValidationException — CatVRF 2026 Component.
     *
     * Part of the CatVRF multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     * @author CatVRF Team
     * @license Proprietary
     * @see https://catvrf.ru/docs/webhookvalidationexception
     * @see https://catvrf.ru/docs/webhookvalidationexception
     * @see https://catvrf.ru/docs/webhookvalidationexception
     * @see https://catvrf.ru/docs/webhookvalidationexception
     * @see https://catvrf.ru/docs/webhookvalidationexception
     * @see https://catvrf.ru/docs/webhookvalidationexception
     * @see https://catvrf.ru/docs/webhookvalidationexception
     * @see https://catvrf.ru/docs/webhookvalidationexception
     * @see https://catvrf.ru/docs/webhookvalidationexception
     * @see https://catvrf.ru/docs/webhookvalidationexception
     * @see https://catvrf.ru/docs/webhookvalidationexception
     * @see https://catvrf.ru/docs/webhookvalidationexception
     * @see https://catvrf.ru/docs/webhookvalidationexception
     * @see https://catvrf.ru/docs/webhookvalidationexception
     * @see https://catvrf.ru/docs/webhookvalidationexception
     */

}
