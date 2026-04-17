<?php declare(strict_types=1);

namespace App\Domains\Education\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCourseRequest extends FormRequest
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
            'description'       => ['required', 'string', 'min:10', 'max:10000'],
            'category'          => ['required', 'string', 'max:128'],
            'format'            => ['required', 'string', 'in:online,offline,hybrid'],
            'price'             => ['required', 'numeric', 'min:0'],
            'duration_hours'    => ['required', 'numeric', 'min:0.5'],
            'level'             => ['required', 'string', 'in:beginner,intermediate,advanced,expert'],
            'language'          => ['sometimes', 'string', 'max:32'],
            'is_certified'      => ['sometimes', 'boolean'],
            'max_students'      => ['sometimes', 'integer', 'min:1'],
            'starts_at'         => ['sometimes', 'date'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required'          => 'Название курса обязательно.',
            'description.required'    => 'Описание обязательно.',
            'category.required'       => 'Категория обязательна.',
            'format.required'         => 'Формат обучения обязателен.',
            'price.required'          => 'Цена обязательна.',
            'duration_hours.required' => 'Продолжительность обязательна.',
            'level.required'          => 'Уровень сложности обязателен.',
        ];
    }
}
