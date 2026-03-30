<?php declare(strict_types=1);

namespace App\Http\Requests\Api\Tickets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BaseApiRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * По умолчанию авторизован, проверяется в подклассах.
         */
        public function authorize(): bool
        {
            return true;
        }

        /**
         * Возвращает correlation_id из заголовка или генерирует новый.
         */
        public function getCorrelationId(): string
        {
            return $this->header('X-Correlation-ID', (string) Str::uuid());
        }

        /**
         * Форматированный ответ при ошибке валидации (API Канон 2026).
         */
        protected function failedValidation(Validator $validator)
        {
            $correlationId = $this->getCorrelationId();

            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'correlation_id' => $correlationId,
                    'error' => 'Ошибка валидации данных',
                    'details' => $validator->errors(),
                ], 422)
            );
        }
}
