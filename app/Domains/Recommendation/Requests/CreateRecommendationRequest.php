<?php declare(strict_types=1);

namespace App\Domains\Recommendation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateRecommendationRequest extends FormRequest
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
            'vertical'          => ['required', 'string', 'max:64'],
            'context'           => ['required', 'string', 'in:homepage,product,cart,checkout,post_purchase,email'],
            'limit'             => ['sometimes', 'integer', 'min:1', 'max:100'],
            'exclude_ids'       => ['sometimes', 'array'],
            'exclude_ids.*'     => ['integer', 'min:1'],
            'filters'           => ['sometimes', 'array'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'vertical.required' => 'Вертикаль обязательна.',
            'context.required'  => 'Контекст рекомендации обязателен.',
        ];
    }
}
