<?php declare(strict_types=1);

namespace App\Http\Requests\Music;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class creation extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Determine if the user is authorized to make this request.
         */
        public function authorize(): bool
        {
            // Fraud check for lesson management
            $fraudCheck = FraudControlService::check([
                'user_id' => auth()->id(),
                'ip' => $this->ip(),
                'action' => 'lesson_mutation',
                'tenant_id' => tenant()->id,
            ]);

            if (!$fraudCheck->isAllowed()) {
                Log::channel('fraud_alert')->warning('Blocked music lesson mutation attempt', [
                    'user_id' => auth()->id(),
                    'ip' => $this->ip(),
                    'reason' => $fraudCheck->reason(),
                ]);
                return false;
            }

            return auth()->check();
        }

        /**
         * Get the validation rules that apply to the request.
         */
        public function rules(): array
        {
            return [
                'name' => 'required|string|max:255',
                'store_id' => 'required|integer|exists:music_stores,id',
                'instructor_name' => 'required|string|max:255',
                'instrument_id' => 'nullable|integer|exists:music_instruments,id',
                'description' => 'nullable|string',
                'hourly_rate' => 'required|integer|min:0',
                'student_capacity' => 'required|integer|min:1',
                'difficulty_level' => 'required|in:beginner,intermediate,advanced',
                'duration_minutes' => 'required|integer|min:15',
                'is_active' => 'boolean',
                'tags' => 'nullable|array',
            ];
        }

        /**
         * Get custom messages for validator errors.
         */
        public function messages(): array
        {
            return [
                'name.required' => 'Введите название урока.',
                'store_id.required' => 'Укажите магазин, предоставляющий обучение.',
                'instructor_name.required' => 'Введите имя инструктора.',
                'hourly_rate.required' => 'Укажите почасовую ставку (в копейках).',
                'student_capacity.required' => 'Укажите максимальное количество учеников.',
                'difficulty_level.in' => 'Выберите корректный уровень сложности: beginner, intermediate или advanced.',
            ];
        }

        /**
         * Prepare the data for validation.
         */
        protected function prepareForValidation(): void
        {
            if (empty($this->correlation_id)) {
                $this->merge([
                    'correlation_id' => (string) Str::uuid(),
                ]);
            }
        }
}
