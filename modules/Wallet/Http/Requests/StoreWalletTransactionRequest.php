<?php declare(strict_types=1);

namespace Modules\Wallet\Http\Requests;

use App\Services\FraudControlService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Wallet\Enums\TransactionType;

final class StoreWalletTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @param FraudControlService $fraudControl
     * @return bool
     */
    public function authorize(FraudControlService $fraudControl): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // CANON 2026: Fraud Check in FormRequest
        $fraudResult = $fraudControl->check(
            userId: $user->id,
            operationType: 'wallet_transaction',
            amount: (int) ($this->input('amount') * 100),
            ipAddress: $this->ip(),
            correlationId: $this->header('X-Correlation-ID')
        );

        if ($fraudResult['decision'] === 'block' && !$user->is_admin) {
            \Illuminate\Support\Facades\Log::channel('audit')->warning('Fraud check blocked wallet transaction request', [
                'class' => __CLASS__,
                'score' => $fraudResult['score'],
                'user_id' => $user->id,
                'correlation_id' => $this->header('X-Correlation-ID'),
            ]);
            return false;
        }

        // Ensure the user belongs to the current tenant
        return $user->tenant_id === tenant('id');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'currency' => ['nullable', 'string', 'in:RUB,USD,EUR'], // As per canon, should be strict
            'type' => ['required', 'string', Rule::in(array_column(TransactionType::cases(), 'value'))],
            'metadata' => ['nullable', 'array'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Сумма транзакции обязательна.',
            'amount.numeric' => 'Сумма должна быть числовым значением.',
            'amount.min' => 'Минимальная сумма транзакции составляет 0.01.',
            'amount.max' => 'Максимальная сумма транзакции составляет 99999.99.',
            'currency.in' => 'Указана недопустимая валюта.',
            'type.required' => 'Тип транзакции обязателен.',
            'type.in' => 'Указан недопустимый тип транзакции. Допустимые значения: deposit, withdrawal.',
            'description.max' => 'Описание не должно превышать 500 символов.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        // CANON 2026: Log validation errors and throw exception
        \Illuminate\Support\Facades\Log::channel('audit')->warning('Wallet transaction validation failed', [
            'errors' => $validator->errors(),
            'correlation_id' => $this->header('X-Correlation-ID'),
        ]);

        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'correlation_id' => $this->header('X-Correlation-ID'),
            ], 422)
        );
    }
}


