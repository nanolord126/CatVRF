<?php declare(strict_types=1);

namespace App\Http\Requests\Music;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicStoreRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Determine if the user is authorized to make this request.
         */
        public function authorize(): bool
        {
            FraudControlService::check();
            return true;
        }

        /**
         * Get the validation rules that apply to the request.
         */
        public function rules(): array
        {
            return [
                'name' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:500'],
                'type' => ['required', 'in:shop,school,studio,mixed'],
                'geo_point' => ['nullable', 'array'],
                'schedule' => ['nullable', 'array'],
                'is_verified' => ['boolean'],
                'tags' => ['nullable', 'array'],
            ];
        }

        /**
         * Get custom messages for validator errors.
         */
        public function messages(): array
        {
            return [
                'name.required' => 'Название магазина музыки обязательно.',
                'address.required' => 'Адрес магазина музыки обязателен.',
                'type.in' => 'Выбран некорректный тип магазина музыки.',
            ];
        }
}
