declare(strict_types=1);

namespace App\Http\Requests\Payment;

use App\Http\Requests\BaseApiRequest;

/**
 * Capture Payment Request.
 * Валидация данных для захвата платежа (списание денег).
 *
 * Rules:
 * - payment_id: required, exists in payments table
 * - amount: optional, integer, > 0 (full amount if not provided)
 */
final class CapturePaymentRequest extends BaseApiRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'payment_id.required' => 'Payment ID required',
            'payment_id.exists' => 'Payment not found',
            'amount.integer' => 'Amount must be integer (kopeks)',
            'amount.min' => 'Amount minimum 100 kopeks',
        ];
    }
}
