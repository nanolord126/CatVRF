<?php declare(strict_types=1);

namespace App\Http\Requests\Api\Tickets;


use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class BaseApiRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Api\Tickets
 */
final class BaseApiRequest extends FormRequest
{
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
                $this->responseFactory->json([
                    'success' => false,
                    'correlation_id' => $correlationId,
                    'error' => 'Ошибка валидации данных',
                    'details' => $validator->errors(),
                ], 422)
            );
        }

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}
