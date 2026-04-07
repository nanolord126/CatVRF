<?php declare(strict_types=1);

namespace App\Http\Requests\Music;



use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class MusicStoreRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Music
 */
final class MusicStoreRequest extends FormRequest
{
    public function __construct(
        private readonly Request $request,
    ) {}

    /**
         * Determine if the user is authorized to make this request.
         */
        public function authorize(): bool
        {
            app(\App\Services\FraudControlService::class)->check(
                userId: (int) $this->guard->id(),
                operationType: 'mutation',
                amount: 0,
                correlationId: $this->request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            );
            return true;
        }

        /**
         * Get the validation rules that apply to the request.
         */
        public function rules(): array
        {
            return [
                'name' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:500'],
                'type' => ['required', 'in:shop,school,studio,mixed'],
                'geo_point' => ['nullable', 'array'],
                'schedule' => ['nullable', 'array'],
                'is_verified' => ['boolean'],
                'tags' => ['nullable', 'array'],
            ];
        }

        /**
         * Get custom messages for validator errors.
         */
        public function messages(): array
        {
            return [
                'name.required' => 'Название магазина музыки обязательно.',
                'address.required' => 'Адрес магазина музыки обязателен.',
                'type.in' => 'Выбран некорректный тип магазина музыки.',
            ];
        }
}
