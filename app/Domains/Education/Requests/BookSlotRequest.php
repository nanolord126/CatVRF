<?php declare(strict_types=1);

namespace App\Domains\Education\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BookSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'slot_id' => ['required', 'integer', 'exists:education_slots,id'],
            'biometric_hash' => ['nullable', 'string', 'max:255'],
            'payment_method_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'User not found',
            'slot_id.required' => 'Slot ID is required',
            'slot_id.exists' => 'Slot not found',
        ];
    }
}
