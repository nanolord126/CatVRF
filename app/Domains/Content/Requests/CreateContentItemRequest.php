<?php declare(strict_types=1);

namespace App\Domains\Content\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateContentItemRequest extends FormRequest
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
            'title'             => ['required', 'string', 'min:3', 'max:512'],
            'type'              => ['required', 'string', 'in:article,video,shorts,podcast,image,document'],
            'body'              => ['sometimes', 'string'],
            'url'               => ['sometimes', 'url', 'max:512'],
            'vertical'          => ['required', 'string', 'max:64'],
            'status'            => ['sometimes', 'string', 'in:draft,published,archived'],
            'published_at'      => ['sometimes', 'date'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required'    => 'Заголовок контента обязателен.',
            'type.required'     => 'Тип контента обязателен.',
            'vertical.required' => 'Вертикаль обязательна.',
        ];
    }
}
