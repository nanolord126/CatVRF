declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final /**
 * PharmacyOrderStoreRequest
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PharmacyOrderStoreRequest extends FormRequest
{
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
