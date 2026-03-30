<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AddProductRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
