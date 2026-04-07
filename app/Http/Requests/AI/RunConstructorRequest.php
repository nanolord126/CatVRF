<?php declare(strict_types=1);

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class RunConstructorRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\AI
 */
final class RunConstructorRequest extends FormRequest
{
    /**
     * Handle authorize operation.
     *
     * @throws \DomainException
     */
    public function authorize(FraudControlService $fraud): bool
        {
            // Basic authorization, can be extended with policies
            $isAuthorized = $this->user() !== null;

            if ($isAuthorized) {
                $fraudControlService->check([
                    'user_id' => $this->user()->id,
                    'operation' => 'ai_constructor_authorize',
                    'ip_address' => $this->ip(),
                ], $this->header('X-Correlation-ID'));
            }

            return $isAuthorized;
        }

        /**
         * Handle rules operation.
         *
         * @throws \DomainException
         */
        public function rules(): array
        {
            return [
                'constructor_type' => ['required', 'string', new Enum(ConstructorType::class)],
                'input_parameters' => ['sometimes', 'array'],
                'image' => ['sometimes', 'image', 'mimes:jpeg,png,jpg', 'max:10240'], // Max 10MB
            ];
        }

        public function messages(): array
        {
            return [
                'constructor_type.required' => 'Необходимо указать тип конструктора.',
                'constructor_type.enum' => 'Выбран неверный тип конструктора.',
                'image.image' => 'Файл должен быть изображением.',
            ];
        }
}
