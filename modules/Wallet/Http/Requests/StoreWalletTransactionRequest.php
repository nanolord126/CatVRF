<?php declare(strict_types=1);

namespace Modules\Wallet\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StoreWalletTransactionRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(): bool
        {
            // CANON 2026: Fraud Check in FormRequest
            if (class_exists(\App\Services\Fraud\FraudControlService::class) && auth()->check()) {
                $fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass());
                if ($fraudScore > 0.7 && !auth()->user()->hasRole('admin')) {
                    \Illuminate\Support\Facades\Log::channel('audit')->warning('Fraud check blocked request', ['class' => __CLASS__, 'score' => $fraudScore]);
                    return false;
                }
            }
            return auth()->check() && auth()->user()->tenant_id === tenant('id');
        }
    
        public function rules(): array
        {
            return [
                'amount' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
                'currency' => ['nullable', 'string', 'in:RUB,USD,EUR'],
                'type' => ['required', 'string', 'in:deposit,withdrawal'],
                'metadata' => ['nullable', 'array'],
                'description' => ['nullable', 'string', 'max:500'],
            ];
        }
    
        public function messages(): array
        {
            return [
                'amount.required' => 'Сумма обязательна',
                'amount.numeric' => 'Сумма должна быть числом',
                'amount.min' => 'Минимальная сумма 0.01 руб',
                'amount.max' => 'Максимальная сумма 99999.99 руб',
                'currency.in' => 'Недопустимая валюта',
                'type.required' => 'Тип операции обязателен',
                'type.in' => 'Тип операции должен быть deposit или withdrawal',
                'description.max' => 'Описание не более 500 символов',
            ];
        }
    
        protected function failedValidation(Validator $validator): void
        {
            parent::failedValidation($validator);
        }
}
