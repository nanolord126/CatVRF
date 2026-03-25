declare(strict_types=1);

namespace App\Http\Requests\Promo;

use App\Http\Requests\BaseApiRequest;

/**
 * Apply Promo Request.
 * Валидация данных для применения промокода.
 *
 * Rules:
 * - code: required, string, max 50, exists in promo_campaigns
 * - order_amount: required, integer, > 0
 * - vertical: sometimes, string (for vertical-specific checks)
 */
final class ApplyPromoRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'order_amount' => ['required', 'integer', 'min:100'],
            'vertical' => ['sometimes', 'string', 'in:beauty,food,hotels,auto'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Promo code required',
            'code.string' => 'Promo code must be string',
            'code.max' => 'Promo code max 50 characters',
            'order_amount.required' => 'Order amount required',
            'order_amount.integer' => 'Order amount must be integer (kopeks)',
            'order_amount.min' => 'Order amount minimum 100 kopeks',
            'vertical.in' => 'Invalid vertical',
        ];
    }
}
