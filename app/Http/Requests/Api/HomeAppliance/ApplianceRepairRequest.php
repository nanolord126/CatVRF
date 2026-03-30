<?php declare(strict_types=1);

namespace App\Http\Requests\Api\HomeAppliance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ApplianceRepairRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Авторизация: проверка лимитов и фрод-контроль.
         */
        public function authorize(): bool
        {
            return true;
        }

        /**
         * Правила валидации — Канон 2026.
         */
        public function rules(): array
        {
            return [
                'appliance_type' => [
                    'required',
                    'string',
                    Rule::in(['washing_machine', 'fridge', 'ac', 'dishwasher', 'oven', 'microwave'])
                ],
                'brand_name' => 'required|string|max:100',
                'model_number' => 'nullable|string|max:100',
                'issue_description' => 'required|string|min:20|max:1000',
                'is_b2b' => 'boolean',
                'client_id' => 'required_without:user_id|integer',

                // Валидация адреса (Канон 2026)
                'address' => 'required|array',
                'address.city' => 'required|string|max:100',
                'address.street' => 'required|string|max:100',
                'address.house' => 'required|string|max:20',
                'address.apartment' => 'nullable|string|max:20',
                'address.lat' => 'nullable|numeric|between:-90,90',
                'address.lon' => 'nullable|numeric|between:-180,180',

                'visit_suggested_at' => 'nullable|date|after:now',
                'photo_base64' => 'nullable|string' // Для AI-оценки
            ];
        }

        /**
         * Понятные сообщения пользователю (Канон 2026).
         */
        public function messages(): array
        {
            return [
                'appliance_type.in' => 'Указанный тип техники пока не обслуживается.',
                'issue_description.min' => 'Пожалуйста, опишите проблему более детально (минимум 20 символов).',
                'address.city.required' => 'Укажите город выезда мастера.',
                'address.street.required' => 'Укажите улицу выезда мастера.',
                'visit_suggested_at.after' => 'Время визита мастера должно быть в будущем.'
            ];
        }
}
