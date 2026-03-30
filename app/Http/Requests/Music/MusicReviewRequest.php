<?php declare(strict_types=1);

namespace App\Http\Requests\Music;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicReviewRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Determine if the user is authorized to make this request.
         */
        public function authorize(): bool
        {
            // Fraud check for reviews
            $fraudCheck = FraudControlService::check([
                'user_id' => auth()->id(),
                'ip' => $this->ip(),
                'action' => 'review_submission',
                'tenant_id' => tenant()->id,
            ]);

            if (!$fraudCheck->isAllowed()) {
                Log::channel('fraud_alert')->warning('Blocked review submission attempt', [
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
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'required|string|max:2000',
                'instrument_id' => 'nullable|integer|exists:music_instruments,id',
                'studio_id' => 'nullable|integer|exists:music_studios,id',
                'lesson_id' => 'nullable|integer|exists:music_lessons,id',
                'is_published' => 'boolean',
                'is_verified_purchase' => 'boolean',
                'tags' => 'nullable|array',
            ];
        }

        /**
         * Get custom messages for validator errors.
         */
        public function messages(): array
        {
            return [
                'rating.required' => 'Укажите оценку от 1 до 5 звезд.',
                'comment.required' => 'Пожалуйста, оставьте описание вашего отзыва.',
                'comment.max' => 'Максимальная длина отзыва 2000 символов.',
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

        /**
         * Ensure at least one linked entity is provided.
         */
        public function withValidator($validator): void
        {
            $validator->after(function ($validator) {
                if (!$this->instrument_id && !$this->studio_id && !$this->lesson_id) {
                    $validator->errors()->add('linked_entity', 'Отзыв должен быть привязан к инструменту, студии или уроку.');
                }
            });
        }
}
