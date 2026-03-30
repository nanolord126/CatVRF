<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PharmacyOrderStoreRequest extends Model
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
                'pharmacy_id' => ['required', 'integer', 'exists:pharmacies,id'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.medicine_id' => ['required', 'integer', 'exists:pharmacy_medicines,id'],
                'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
                'prescription_data' => ['nullable', 'string', 'max:5000'],
            ];
        }

        public function messages(): array
        {
            return [
                'pharmacy_id.required' => 'Выберите аптеку',
                'pharmacy_id.exists' => 'Аптека не найдена',
                'items.required' => 'Добавьте хотя бы один препарат',
                'items.*.medicine_id.required' => 'Укажите ID препарата',
                'items.*.quantity.required' => 'Укажите количество',
                'items.*.quantity.min' => 'Минимальное количество: 1',
            ];
        }
}
