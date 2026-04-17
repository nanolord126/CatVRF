<?php declare(strict_types=1);

namespace App\Domains\Auto\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAutoCatalogBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id' => ['nullable', 'integer', 'min:1'],
            'name'              => ['required', 'string', 'min:2', 'max:255'],
            'country'           => ['sometimes', 'string', 'max:64'],
            'logo_url'          => ['sometimes', 'url', 'max:512'],
            'description'       => ['sometimes', 'string', 'max:2000'],
            'is_active'         => ['sometimes', 'boolean'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Название бренда обязательно.',
        ];
    }
}
