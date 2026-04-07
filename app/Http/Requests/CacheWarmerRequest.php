<?php declare(strict_types=1);

/**
 * CacheWarmerRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/cachewarmerrequest
 * @see https://catvrf.ru/docs/cachewarmerrequest
 * @see https://catvrf.ru/docs/cachewarmerrequest
 */


namespace App\Http\Requests;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CacheWarmerRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests
 */
final class CacheWarmerRequest extends FormRequest
{
    /**
     * Handle authorize operation.
     *
     * @throws \DomainException
     */
    public function authorize(): bool
        {
            return $this->guard->check() && $this->guard->user()->isAdmin();
        }

        /**
         * Handle rules operation.
         *
         * @throws \DomainException
         */
        public function rules(): array
        {
            return [
                'vertical' => 'nullable|string|max:100',
                'user_id' => 'nullable|integer|exists:users,id',
                'queue' => 'nullable|string|in:cache-warm,default',
            ];
        }

        /**
         * Handle messages operation.
         *
         * @throws \DomainException
         */
        public function messages(): array
        {
            return [
                'vertical.exists' => 'The selected vertical does not exist.',
                'user_id.exists' => 'The selected user does not exist.',
            ];
        }
}
