<?php declare(strict_types=1);

namespace App\Http\Requests\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InitPaymentRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(): bool
        {
            return auth()->check();
        }

        public function rules(): array
        {
            return [
                'operation_type' => [
                    'required',
                    'string',
                    'in:beauty_appointment,food_order,hotel_booking,taxi_ride',
                ],
                'amount' => ['required', 'integer', 'min:100', 'max:10000000'],
                'currency' => ['sometimes', 'string', 'in:RUB,USD,EUR'],
                'hold' => ['sometimes', 'boolean'],
                'idempotency_key' => ['sometimes', 'uuid'],
            ];
        }

        public function messages(): array
        {
            return [
                'operation_type.required' => 'Operation type required',
                'operation_type.in' => 'Invalid operation type',
                'amount.required' => 'Amount required',
                'amount.integer' => 'Amount must be integer (kopeks)',
                'amount.min' => 'Amount minimum 100 kopeks',
                'amount.max' => 'Amount maximum 10000000 kopeks',
                'currency.in' => 'Invalid currency (RUB/USD/EUR)',
                'hold.boolean' => 'Hold must be boolean',
                'idempotency_key.uuid' => 'Idempotency key must be UUID',
            ];
        }
}
