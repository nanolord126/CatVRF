<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConfirmPaymentRequest extends Model
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
                'payment_id' => 'required|string',
            ];
        }

        public function messages(): array
        {
            return [
                'payment_id.required' => 'Укажите ID платежа',
            ];
        }
}
