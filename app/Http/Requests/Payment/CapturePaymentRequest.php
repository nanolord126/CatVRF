<?php declare(strict_types=1);

namespace App\Http\Requests\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CapturePaymentRequest extends Model
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
