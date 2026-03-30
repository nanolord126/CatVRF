<?php declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ReferralClaimRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(): bool
        {
            // CANON 2026: Fraud Check in FormRequest
            if (auth()->check()) {
                $correlationId = $this->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();
                $fraudResult = app(\App\Services\FraudControlService::class)->check(
                    (int) auth()->id(),
                    'form_request',
                    (int) ($this->input('amount', 0)),
                    $this->ip(),
                    $this->header('X-Device-Fingerprint'),
                    $correlationId,
                );
                if ($fraudResult['decision'] === 'block') {
                    \Illuminate\Support\Facades\Log::channel('fraud_alert')->warning('FormRequest blocked', [
                        'class'          => __CLASS__,
                        'correlation_id' => $correlationId,
                        'score'          => $fraudResult['score'],
                    ]);
                    return false;
                }
            }
            return auth()->check();
        }

        public function rules(): array
        {
            return [
                'referral_id' => ['required', 'integer', 'exists:referrals,id'],
                'confirm_turnover' => ['required', 'boolean'],  // Подтвердить, что оборот достигнут
            ];
        }

        public function messages(): array
        {
            return [
                'referral_id.required' => 'Referral ID is required',
                'referral_id.integer' => 'Referral ID must be an integer',
                'referral_id.exists' => 'Referral not found',
                'confirm_turnover.required' => 'Turnover confirmation is required',
                'confirm_turnover.boolean' => 'Turnover confirmation must be true or false',
            ];
        }
}
