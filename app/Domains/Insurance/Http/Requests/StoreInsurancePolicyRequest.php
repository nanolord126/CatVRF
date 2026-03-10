<?php

namespace App\Domains\Insurance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInsurancePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'policyholder_id' => 'required|exists:users,id',
            'policy_number' => 'required|string|unique:insurance_policies',
            'type' => 'required|in:health,auto,home,life',
            'status' => 'required|in:active,expired,cancelled',
            'premium_amount' => 'required|numeric|min:0',
            'coverage_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ];
    }
}
