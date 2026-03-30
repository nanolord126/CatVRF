<?php declare(strict_types=1);

namespace App\Http\Requests\Furniture;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FurnitureSearchRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(): bool
        {
            // Simple authentication check - production should include permission check
            return auth()->check();
        }

        public function rules(): array
        {
            return [
                'q' => ['nullable', 'string', 'max:100'],
                'category_id' => ['nullable', 'integer', 'exists:furniture_categories,id'],
                'room_type_id' => ['nullable', 'integer', 'exists:furniture_room_types,id'],
                'min_price' => ['nullable', 'integer', 'min:0'],
                'max_price' => ['nullable', 'integer', 'min:0'],
                'style' => ['nullable', 'string', 'in:scandi,loft,modern,classic,industrial'],
                'has_3d' => ['nullable', 'boolean'],
                'sort_by' => ['nullable', 'string', Rule::in(['price_asc', 'price_desc', 'newest', 'popularity'])],
                'page' => ['nullable', 'integer', 'min:1'],
            ];
        }

        public function messages(): array
        {
            return [
                'category_id.exists' => 'Selected category is invalid or inactive.',
                'style.in' => 'Style must be one of: scandi, loft, modern, classic, industrial.',
            ];
        }
}
