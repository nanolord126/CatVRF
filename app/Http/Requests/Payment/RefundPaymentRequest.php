declare(strict_types=1);

namespace App\Http\Requests\Payment;

use App\Http\Requests\BaseApiRequest;

/**
 * Refund Payment Request.
 * Валидация данных для возврата платежа.
 *
 * Rules:
 * - payment_id: required, exists
 * - amount: optional, integer, > 0 (full amount if not provided)
 * - reason: optional, string, max 500
 */
final class RefundPaymentRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'amount' => ['sometimes', 'integer', 'min:100'],
            'reason' => ['sometimes', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_id.required' => 'Payment ID required',
            'payment_id.exists' => 'Payment not found',
            'amount.integer' => 'Amount must be integer (kopeks)',
            'amount.min' => 'Amount minimum 100 kopeks',
            'reason.string' => 'Reason must be string',
            'reason.max' => 'Reason max 500 characters',
        ];
    }
}
