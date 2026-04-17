<?php declare(strict_types=1);

namespace App\Domains\Bonuses\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AwardBonusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('award_bonuses');
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:loyalty,referral,turnover,promo,migration'],
            'source_type' => ['nullable', 'string'],
            'source_id' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
