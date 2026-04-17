<?php declare(strict_types=1);

namespace App\Domains\AI\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAIModelRequest extends FormRequest
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
            'name'              => ['required', 'string', 'min:2', 'max:255'],
            'provider'          => ['required', 'string', 'in:openai,gigachat,stable_diffusion,custom'],
            'model_version'     => ['required', 'string', 'max:128'],
            'config'            => ['sometimes', 'array'],
            'is_active'         => ['sometimes', 'boolean'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'vertical.required'      => 'Вертикаль обязательна.',
            'provider.required'      => 'Провайдер AI обязателен.',
            'model_version.required' => 'Версия модели обязательна.',
        ];
    }
}
