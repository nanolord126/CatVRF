<?php

declare(strict_types=1);

namespace Modules\FraudDetection\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\FraudDetection\Application\DTOs\FraudCheckData;

final class FraudCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Авторизация обычно выполняется через middleware (например, проверка API-ключа)
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_id' => 'required|string|max:255',
            'amount' => 'required|integer|min:1',
            'user_id' => 'required|integer|exists:users,id',
            'device_fingerprint' => 'required|string|max:255',
            'metadata' => 'sometimes|array',
        ];
    }

    public function toData(): FraudCheckData
    {
        return FraudCheckData::fromRequest($this);
    }
}
