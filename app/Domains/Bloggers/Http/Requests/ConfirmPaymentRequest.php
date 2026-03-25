declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Bloggers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final /**
 * ConfirmPaymentRequest
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ConfirmPaymentRequest extends FormRequest
{
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
