<?php

declare(strict_types=1);

namespace App\Domains\DemandForecast\Requests\B2B;

use Illuminate\Foundation\Http\FormRequest;

/**
 * B2B Form Request: создание DemandForecast.
 *
 * CANON 2026 — Layer 7: Requests (B2B namespace).
 */
final class CreateDemandForecastRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole(['tenant_owner', 'tenant_manager', 'b2b_manager']);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'min:2', 'max:255'],
            'description'       => ['nullable', 'string', 'max:5000'],
            'price_cents'       => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'status'            => ['sometimes', 'string', 'in:active,draft,archived'],
            'tags'              => ['nullable', 'array', 'max:20'],
            'tags.*'            => ['string', 'max:50'],
            'inn'               => ['nullable', 'string', 'size:10'],
            'business_card_id'  => ['nullable', 'integer', 'exists:business_groups,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Название обязательно.',
            'name.max'      => 'Название не может быть длиннее 255 символов.',
        ];
    }

    /**
     * Подготовка данных перед валидацией.
     */
    protected function prepareForValidation(): void
    {
        if (!empty($this->inn) && !empty($this->business_card_id)) {
            $this->merge(['is_b2b' => true]);
        }
    }
}
