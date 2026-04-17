<?php declare(strict_types=1);

namespace App\Domains\Notifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('send_notifications');
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'exists:users,id'],
            'type' => ['required', 'string'],
            'channel' => ['required', 'in:email,push,sms,telegram,in_app'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'data' => ['nullable', 'array'],
        ];
    }
}
