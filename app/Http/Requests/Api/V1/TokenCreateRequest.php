<?php declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TokenCreateRequest extends Model
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
            return auth()->check(); // Public endpoint
        }

        public function rules(): array
        {
            return [
                'email' => 'required|email:rfc,dns',
                'password' => 'required|string|min:8',
                'name' => 'required|string|max:255',
                'abilities' => 'nullable|array|min:1',
                'abilities.*' => 'string|in:*,create:order,read:wallet,admin:tenant,view:analytics',
            ];
        }

        public function messages(): array
        {
            return [
                'email.required' => 'Email is required',
                'email.email' => 'Invalid email format',
                'password.required' => 'Password is required',
                'password.min' => 'Password must be at least 8 characters',
                'name.required' => 'Token name is required',
                'abilities.min' => 'At least one ability must be specified',
            ];
        }

        public function validated(): array
        {
            return parent::validated();
        }
}
