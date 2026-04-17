<?php declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateBookRequest extends FormRequest
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
            'title'             => ['required', 'string', 'min:1', 'max:512'],
            'author'            => ['required', 'string', 'min:2', 'max:255'],
            'isbn'              => ['sometimes', 'string', 'max:20'],
            'genre'             => ['sometimes', 'string', 'max:128'],
            'price'             => ['required', 'numeric', 'min:0'],
            'stock'             => ['required', 'integer', 'min:0'],
            'description'       => ['sometimes', 'string', 'max:4000'],
            'published_at'      => ['sometimes', 'date'],
            'is_digital'        => ['sometimes', 'boolean'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required'  => 'Название книги обязательно.',
            'author.required' => 'Автор обязателен.',
            'price.required'  => 'Цена обязательна.',
            'stock.required'  => 'Остаток обязателен.',
        ];
    }
}
