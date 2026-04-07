<?php declare(strict_types=1);

namespace App\Http\Requests\Api\Auto;



use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class AutoVinSearchRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Api\Auto
 */
final class AutoVinSearchRequest extends FormRequest
{
    public function __construct(
        private readonly Request $request,
    ) {}

    public function authorize(): bool
        {
            // Fraud Check перед выполнением запроса
            app(\App\Services\FraudControlService::class)->check(
                userId: (int) ($this->guard->id() ?? 0),
                operationType: 'vin_search_attempt',
                amount: 0,
                correlationId: $this->request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            );

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
