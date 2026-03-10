<?php

namespace App\Domains\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSportsMembershipRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'athlete_id' => 'required|exists:users,id',
            'program_id' => 'required|exists:sports_programs,id',
            'tier' => 'required|in:basic,pro,elite',
        ];
    }
}
