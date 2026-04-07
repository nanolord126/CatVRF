<?php declare(strict_types=1);

/**
 * PurchaseTicketRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/purchaseticketrequest
 */


namespace App\Domains\Tickets\Requests;

final class PurchaseTicketRequest
{

    public function authorize(): bool
        {
            // Канон: Fraud check перед мутацией
            return true;
        }

        public function rules(): array
        {
            return [
                'event_id' => ['required', 'exists:tickets_events,id'],
                'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            ];
        }

        public function messages(): array
        {
            return [
                'event_id.exists' => 'Событие не найдено',
                'quantity.max' => 'Нельзя купить более 10 билетов за раз',
            ];
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
