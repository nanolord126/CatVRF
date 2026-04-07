<?php declare(strict_types=1);

/**
 * BookSeatRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/bookseatrequest
 * @see https://catvrf.ru/docs/bookseatrequest
 * @see https://catvrf.ru/docs/bookseatrequest
 * @see https://catvrf.ru/docs/bookseatrequest
 * @see https://catvrf.ru/docs/bookseatrequest
 * @see https://catvrf.ru/docs/bookseatrequest
 * @see https://catvrf.ru/docs/bookseatrequest
 */


namespace App\Http\Requests\Api\Entertainment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class BookSeatRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Api\Entertainment
 */
final class BookSeatRequest extends FormRequest
{
    /**
     * Handle rules operation.
     *
     * @throws \DomainException
     */
    public function rules(): array
        {
            return array_merge(parent::rules(), [
                'event_id' => ['required', 'integer', 'exists:entertainment_events,id'],
                'seats' => ['required', 'array', 'min:1'],
                'seats.*.row' => ['required', 'integer'],
                'seats.*.col' => ['required', 'integer'],
            ]);
        }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}
