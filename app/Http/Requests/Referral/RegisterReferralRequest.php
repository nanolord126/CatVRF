<?php declare(strict_types=1);

namespace App\Http\Requests\Referral;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RegisterReferralRequest extends Model
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
                'referral_code' => ['required', 'string', 'size:8'],
                'source_platform' => [
                    'sometimes',
                    'string',
                    'in:dikidi,booking,ostrovok,yandex_eats,flowwow',
                ],
            ];
        }

        public function messages(): array
        {
            return [
                'referral_code.required' => 'Referral code required',
                'referral_code.string' => 'Referral code must be string',
                'referral_code.size' => 'Referral code must be 8 characters',
                'source_platform.in' => 'Invalid source platform',
            ];
        }
}
