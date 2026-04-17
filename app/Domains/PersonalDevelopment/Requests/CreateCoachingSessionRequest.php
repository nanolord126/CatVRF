<?php declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCoachingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_group_id' => ['nullable', 'integer', 'min:1'],
            'coach_id'          => ['required', 'integer', 'min:1'],
            'goal'              => ['required', 'string', 'min:10', 'max:2000'],
            'session_type'      => ['required', 'string', 'in:individual,group,online,offline'],
            'duration_minutes'  => ['required', 'integer', 'in:30,45,60,90,120'],
            'scheduled_at'      => ['required', 'date', 'after:now'],
            'notes'             => ['sometimes', 'string', 'max:1000'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'coach_id.required'        => 'Коуч обязателен.',
            'goal.required'            => 'Цель сессии обязательна.',
            'session_type.required'    => 'Тип сессии обязателен.',
            'duration_minutes.required' => 'Продолжительность обязательна.',
            'scheduled_at.required'    => 'Дата и время сессии обязательны.',
        ];
    }
}
