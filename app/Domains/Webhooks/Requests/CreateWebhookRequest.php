<?php declare(strict_types=1);

namespace App\Domains\Webhooks\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('create_webhooks');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string'],
            'secret' => ['nullable', 'string', 'min:32'],
            'is_active' => ['boolean'],
            'retry_count' => ['integer', 'min:0', 'max:10'],
            'timeout' => ['integer', 'min:1', 'max:120'],
            'headers' => ['nullable', 'array'],
        ];
    }
}
