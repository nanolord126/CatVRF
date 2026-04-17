<?php declare(strict_types=1);

namespace App\Domains\Education\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CalculatePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'user_segment' => ['nullable', 'string', 'in:vip,premium,standard,new'],
            'enrollment_count' => ['nullable', 'integer', 'min:0'],
            'time_slot' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'Course ID is required',
            'course_id.exists' => 'Course not found',
            'user_id.exists' => 'User not found',
            'user_segment.in' => 'Invalid user segment',
            'time_slot.regex' => 'Time slot must be in HH:MM format',
        ];
    }
}
