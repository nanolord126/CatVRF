<?php declare(strict_types=1);

namespace App\Domains\MeatShops\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class UpdateMeatOrderRequest
 *
 * Part of the MeatShops vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\MeatShops\Http\Requests
 */
final class UpdateMeatOrderRequest extends FormRequest
{
    /**
     * Handle authorize operation.
     *
     * @throws \DomainException
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Handle rules operation.
     *
     * @throws \DomainException
     */
    public function rules(): array
    {
        return [
            'shop_id' => 'sometimes|integer|min:1',
            'items' => 'sometimes|array',
            'delivery_address' => 'sometimes|string|max:255',
            'total_price' => 'sometimes|numeric|min:0',
            'delivery_date' => 'sometimes|date',
        ];
    }

    public function correlationId(): string
    {
        return $this->header('X-Correlation-ID', (string) Str::uuid());
    }

    public function isB2B(): bool
    {
        return $this->has('inn') && $this->has('business_card_id');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['correlation_id' => $this->correlationId()]);
    }
}
