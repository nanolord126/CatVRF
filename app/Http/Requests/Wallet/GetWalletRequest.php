declare(strict_types=1);

namespace App\Http\Requests\Wallet;

use App\Http\Requests\BaseApiRequest;

/**
 * Get Wallet Balance Request.
 * Валидация запроса информации о кошельке.
 *
 * Rules: No additional validation needed.
 */
final class GetWalletRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }
}
