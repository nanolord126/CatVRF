<?php declare(strict_types=1);

namespace App\Domains\Consulting\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateConsultantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id'  => ['nullable', 'integer', 'min:1'],
            'name'               => ['required', 'string', 'min:2', 'max:255'],
            'specialization'     => ['required', 'string', 'max:255'],
            'experience_years'   => ['required', 'integer', 'min:0', 'max:70'],
            'hourly_rate'        => ['required', 'numeric', 'min:0'],
            'bio'                => ['sometimes', 'string', 'max:4000'],
            'languages'          => ['sometimes', 'array'],
            'languages.*'        => ['string', 'max:32'],
            'is_online'          => ['sometimes', 'boolean'],
            'tags'               => ['sometimes', 'array'],
            'tags.*'             => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'           => 'Имя консультанта обязательно.',
            'specialization.required' => 'Специализация обязательна.',
            'hourly_rate.required'    => 'Ставка обязательна.',
        ];
    }
}
