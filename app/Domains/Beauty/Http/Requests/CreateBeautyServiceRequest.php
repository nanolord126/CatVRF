<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateBeautyServiceRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Проверка прав.
         */
        public function authorize(): bool
        {
            // КАНОН 2026: Fraud check before mutation
            return FraudControlService::check(
                userId: auth()->id() ?? 0,
                operationType: 'beauty_service_create',
                amount: 0
            );
        }

        /**
         * Правила валидации.
         */
        public function rules(): array
        {
            return [
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'price' => ['required', 'integer', 'min:0'],
                'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
                'salon_id' => ['required', 'exists:beauty_salons,id'],
                'master_id' => ['nullable', 'exists:masters,id'],
                'consumables' => ['nullable', 'array'],
                'consumables.*.name' => ['required', 'string'],
                'consumables.*.quantity' => ['required', 'integer', 'min:1'],
                'tags' => ['nullable', 'array'],
            ];
        }

        /**
         * Сообщения об ошибках.
         */
        public function messages(): array
        {
            return [
                'price.min' => 'Цена не может быть отрицательной.',
                'duration_minutes.min' => 'Продолжительность должна быть не менее 5 минут.',
            ];
        }
}
