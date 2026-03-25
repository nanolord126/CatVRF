declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Bloggers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final /**
 * AddProductRequest
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class AddProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
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
}
