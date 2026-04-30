<?php

declare(strict_types=1);

namespace Modules\Warehouse\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for adding stock
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final class AddStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_item_id' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string'],
            'user_id' => ['nullable', 'integer'],
            'tenant_id' => ['nullable', 'string'],
        ];
    }
}
