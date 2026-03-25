declare(strict_types=1);

namespace App\Http\Requests\Payment;

use App\Http\Requests\BaseApiRequest;

/**
 * Init Payment Request.
 * Валидация данных для инициирования платежа (создание холда).
 *
 * Rules:
 * - operation_type: required, in allowed types
 * - amount: required, integer, > 0, <= 10000000 (max 100000₽)
 * - currency: optional, default RUB
 * - hold: optional, boolean (default true)
 * - idempotency_key: optional, uuid
 */
final class InitPaymentRequest extends BaseApiRequest
{
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
