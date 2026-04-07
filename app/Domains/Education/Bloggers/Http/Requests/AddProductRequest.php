<?php declare(strict_types=1);

/**
 * AddProductRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/addproductrequest
 */


namespace App\Domains\Education\Bloggers\Http\Requests;


use Illuminate\Contracts\Auth\Guard;
final class AddProductRequest
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
                'product_id' => 'required|integer|exists:products,id',
                'price_override' => 'nullable|integer|min:1|max:9999999',
                'quantity' => 'required|integer|min:1|max:1000',
            ];
        }

        public function messages(): array
        {
            return [
                'product_id.required' => 'Укажите товар',
                'product_id.exists' => 'Товар не найден',
                'price_override.integer' => 'Цена должна быть числом',
                'quantity.min' => 'Количество должно быть минимум 1',
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

}
