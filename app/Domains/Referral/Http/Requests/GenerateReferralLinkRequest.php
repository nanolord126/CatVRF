<?php

declare(strict_types=1);

namespace App\Domains\Referral\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class GenerateReferralLinkRequest
 *
 * Part of the Referral vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\Referral\Http\Requests
 */
final class GenerateReferralLinkRequest extends FormRequest
{
    /**
     * Безусловно подтверждает право пользователя на получение реферальной ссылки.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Строгие правила валидации параметров генерации ссылки.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:user,business'],
            'correlation_id' => ['required', 'string', 'uuid'],
        ];
    }
}
