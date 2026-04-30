<?php

declare(strict_types=1);

namespace App\Domains\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class CreateFlashMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'membership_type' => 'required|string|in:monthly,quarterly,annual',
            'duration_days' => 'required|integer|min:1|max:365',
            'base_price' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:0',
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
