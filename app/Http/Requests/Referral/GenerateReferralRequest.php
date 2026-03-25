declare(strict_types=1);

namespace App\Http\Requests\Referral;

use App\Http\Requests\BaseApiRequest;

/**
 * Generate Referral Request.
 * Валидация данных для создания реферальной ссылки.
 *
 * Rules: No additional validation required, uses base auth.
 */
final class GenerateReferralRequest extends BaseApiRequest
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
