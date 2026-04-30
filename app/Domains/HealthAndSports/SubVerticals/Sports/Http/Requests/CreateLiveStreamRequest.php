<?php

declare(strict_types=1);

namespace App\Domains\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class CreateLiveStreamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'trainer_id' => 'required|integer|exists:sports_trainers,id',
            'session_title' => 'required|string|max:255',
            'session_description' => 'nullable|string|max:2000',
            'scheduled_start' => 'required|date|after:now',
            'scheduled_end' => 'required|date|after:scheduled_start',
            'stream_type' => 'required|string|in:group,personal,workshop',
            'max_participants' => 'sometimes|integer|min:1|max:100',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
            'business_group_id' => 'nullable|integer|exists:business_groups,id',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'correlation_id' => $this->header('X-Correlation-ID', Str::uuid()->toString()),
        ]);
    }
}
