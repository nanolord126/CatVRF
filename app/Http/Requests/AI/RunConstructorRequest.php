<?php declare(strict_types=1);

namespace App\Http\Requests\AI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RunConstructorRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(FraudControlService $fraudControlService): bool
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
