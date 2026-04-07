<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос на корректировку остатков.
 *
 * Валидирует product_id, warehouse_id, new_quantity, reason.
 */
final class AdjustStockRequest extends FormRequest
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
            'new_quantity'   => ['required', 'integer', 'min:0'],
            'reason'         => ['required', 'string', 'max:500'],
            'correlation_id' => ['required', 'string', 'uuid'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'product_id.required'     => 'Product ID обязателен.',
            'warehouse_id.required'   => 'Warehouse ID обязателен.',
            'new_quantity.required'    => 'Новое количество обязательно.',
            'new_quantity.min'         => 'Количество не может быть отрицательным.',
            'reason.required'         => 'Причина корректировки обязательна.',
            'correlation_id.required' => 'Correlation ID обязателен.',
            'correlation_id.uuid'     => 'Correlation ID должен быть UUID.',
        ];
    }
}
