<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос на резервирование товара.
 */
final class ReserveStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'product_id'     => ['required', 'integer', 'min:1'],
            'warehouse_id'   => ['required', 'integer', 'min:1'],
            'quantity'        => ['required', 'integer', 'min:1'],
            'source_type'    => ['required', 'string', 'in:cart,order'],
            'source_id'      => ['required', 'integer', 'min:1'],
            'correlation_id' => ['required', 'string', 'uuid'],
            'cart_id'        => ['nullable', 'integer', 'min:1'],
            'order_id'       => ['nullable', 'integer', 'min:1'],
            'expires_at'     => ['nullable', 'date', 'after:now'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'quantity.min'             => 'Количество для резерва должно быть > 0.',
            'source_type.in'           => 'source_type должен быть cart или order.',
            'correlation_id.uuid'     => 'Correlation ID должен быть UUID.',
        ];
    }
}
