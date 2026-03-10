<?php

namespace App\Domains\Clinic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalCardRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:users,id',
            'blood_type' => 'required|in:O+,O-,A+,A-,B+,B-,AB+,AB-',
            'allergies' => 'nullable|string',
            'chronic_conditions' => 'nullable|array',
        ];
    }
}
