<?php declare(strict_types=1);

namespace App\Domains\Legal\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateLegalCaseRequest extends FormRequest
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
            'lawyer_id'         => ['required', 'integer', 'min:1'],
            'type'              => ['required', 'string', 'in:civil,criminal,commercial,family,labor,administrative,intellectual_property'],
            'title'             => ['required', 'string', 'min:5', 'max:512'],
            'description'       => ['required', 'string', 'min:20', 'max:10000'],
            'priority'          => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'budget'            => ['sometimes', 'numeric', 'min:0'],
            'deadline'          => ['sometimes', 'date', 'after_or_equal:today'],
            'attachments'       => ['sometimes', 'array', 'max:20'],
            'attachments.*'     => ['url', 'max:512'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'lawyer_id.required'   => 'Юрист обязателен.',
            'type.required'        => 'Тип дела обязателен.',
            'title.required'       => 'Название дела обязательно.',
            'description.required' => 'Описание обязательно.',
        ];
    }
}
