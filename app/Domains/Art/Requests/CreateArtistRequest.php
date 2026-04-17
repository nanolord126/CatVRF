<?php declare(strict_types=1);

namespace App\Domains\Art\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateArtistRequest extends FormRequest
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
            'bio'               => ['sometimes', 'string', 'max:2000'],
            'style'             => ['sometimes', 'string', 'max:128'],
            'portfolio_url'     => ['sometimes', 'url', 'max:512'],
            'price_from'        => ['sometimes', 'numeric', 'min:0'],
            'price_to'          => ['sometimes', 'numeric', 'gte:price_from'],
            'is_active'         => ['sometimes', 'boolean'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'    => 'Имя артиста обязательно.',
            'price_to.gte'     => 'Максимальная цена должна быть не меньше минимальной.',
        ];
    }
}
