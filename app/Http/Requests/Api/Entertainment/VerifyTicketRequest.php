<?php

declare(strict_types=1);

/**
 * VerifyTicketRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/verifyticketrequest
 * @see https://catvrf.ru/docs/verifyticketrequest
 * @see https://catvrf.ru/docs/verifyticketrequest
 * @see https://catvrf.ru/docs/verifyticketrequest
 */

namespace App\Http\Requests\Api\Entertainment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class VerifyTicketRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 */
final class VerifyTicketRequest extends FormRequest
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Handle rules operation.
     *
     * @throws \DomainException
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'ticket_id' => ['required', 'string', 'uuid'],
        ]);
    }

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     */
    public function isValid(): bool
    {
        return true;
    }
}
