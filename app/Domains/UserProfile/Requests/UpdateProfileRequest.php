<?php declare(strict_types=1);

namespace App\Domains\UserProfile\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('update_profile');
    }

    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar_url' => ['nullable', 'url', 'max:500'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            'preferred_language' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'timezone'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
