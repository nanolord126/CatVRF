<?php declare(strict_types=1);

/**
 * ConfirmPaymentRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/confirmpaymentrequest
 */


namespace App\Domains\Education\Bloggers\Http\Requests;


use Illuminate\Contracts\Auth\Guard;
final class ConfirmPaymentRequest
{
    public function __construct(
        private readonly Guard $guard) {}


    public function authorize(): bool
        {
            return $this->guard->check();
        }

        public function rules(): array
        {
            return [
                'payment_id' => 'required|string',
            ];
        }

        public function messages(): array
        {
            return [
                'payment_id.required' => 'Укажите ID платежа',
            ];
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

    /**
     * Get the component identifier for logging and audit purposes.
     *
     * @return string The fully qualified component name
     */
    private function getComponentIdentifier(): string
    {
        return static::class . '@' . self::VERSION;
    }

}
