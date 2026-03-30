<?php declare(strict_types=1);

namespace App\Http\Requests\Api\Auto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AutoVinSearchRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(): bool
        {
            // Fraud Check перед выполнением запроса
            FraudControlService::check([
                'type' => 'vin_search_attempt',
                'vin' => $this->get('vin'),
                'ip' => $this->ip(),
            ]);

            return true;
        }

        public function rules(): array
        {
            return [
                'vin' => [
                    'required',
                    'string',
                    'size:17',
                    'regex:/^[A-HJ-NPR-Z0-9]+$/i', // Валидные символы VIN (без I, O, Q)
                ],
            ];
        }

        public function messages(): array
        {
            return [
                'vin.required' => 'Введите VIN-код для поиска.',
                'vin.size' => 'VIN должен состоять из 17 символов.',
                'vin.regex' => 'VIN содержит недопустимые символы (I, O, Q запрещены).',
            ];
        }
}
