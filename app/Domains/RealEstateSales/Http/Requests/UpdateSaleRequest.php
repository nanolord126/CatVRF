<?php

declare(strict_types=1);

namespace App\Domains\RealEstateSales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price' => 'sometimes|numeric|min:0.01',
            'status' => 'nullable|string|in:for_sale,sold,off_market',
            'condition' => 'nullable|string|in:excellent,good,fair,needs_work',
        ];
    }
}
