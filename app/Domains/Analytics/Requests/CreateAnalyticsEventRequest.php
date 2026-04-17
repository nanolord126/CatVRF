<?php declare(strict_types=1);

namespace App\Domains\Analytics\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAnalyticsEventRequest extends FormRequest
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
            'event_type'        => ['required', 'string', 'max:128'],
            'vertical'          => ['required', 'string', 'max:64'],
            'action'            => ['required', 'string', 'in:view,click,add_to_cart,purchase,ar_try_on,ai_constructor'],
            'session_id'        => ['sometimes', 'string', 'max:128'],
            'metadata'          => ['sometimes', 'array'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'event_type.required' => 'Тип события обязателен.',
            'vertical.required'   => 'Вертикаль обязательна.',
            'action.required'     => 'Действие обязательно.',
        ];
    }
}
