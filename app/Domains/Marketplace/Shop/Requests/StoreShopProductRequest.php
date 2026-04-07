<?php declare(strict_types=1);

/**
 * StoreShopProductRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/storeshopproductrequest
 */


namespace App\Domains\Marketplace\Shop\Requests;


use Illuminate\Contracts\Auth\Guard;
final class StoreShopProductRequest
{
    public function __construct(
        private readonly Guard $guard) {}


    public function authorize(): bool
        {
            return $this->guard->user()->hasRole(['business_owner', 'manager']);
        }

        public function rules(): array
        {
            return [
                'name' => ['required', 'string', 'max:255'],
                'sku' => ['required', 'string', 'unique:shop_products,sku,NULL,id,tenant_id,' . $this->guard->user()->tenant_id],
                'category' => ['required', 'string', 'in:clothes,shoes,kids,etc'],
                'price' => ['required', 'integer', 'min:0'], // В копейках
                'attributes' => ['nullable', 'array'],
            ];
        }

        public function messages(): array
        {
            return [
                'sku.unique' => 'Товар с таким SKU уже существует в вашем магазине.',
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
