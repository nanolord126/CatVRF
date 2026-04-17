<?php declare(strict_types=1);

namespace App\Domains\Education\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateLearningPathRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'learning_goal' => ['nullable', 'string', 'max:500'],
            'current_level' => ['nullable', 'string', 'in:beginner,elementary,intermediate,upper_intermediate,advanced,expert'],
            'target_level' => ['nullable', 'string', 'in:beginner,elementary,intermediate,upper_intermediate,advanced,expert'],
            'weekly_hours' => ['nullable', 'integer', 'min:1', 'max:40'],
            'preferred_topics' => ['nullable', 'array'],
            'preferred_topics.*' => ['string', 'max:100'],
            'learning_style' => ['nullable', 'string', 'in:visual,auditory,kinesthetic,reading,mixed'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'User not found',
            'course_id.required' => 'Course ID is required',
            'course_id.exists' => 'Course not found',
            'current_level.in' => 'Invalid current level',
            'target_level.in' => 'Invalid target level',
            'weekly_hours.min' => 'Weekly hours must be at least 1',
            'weekly_hours.max' => 'Weekly hours cannot exceed 40',
            'learning_style.in' => 'Invalid learning style',
        ];
    }
}
