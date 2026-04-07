<?php

declare(strict_types=1);

namespace Modules\Bonuses\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Bonuses\Application\DTOs\AwardBonusData;
use App\Services\FraudControlService;

final class AwardBonusRequest extends FormRequest
{
    public function authorize(FraudControlService $fraudControlService): bool
    {
        // Проверка прав, например, только администратор может начислять бонусы вручную
        // $this->user()->can('award', Bonus::class);
        $fraudControlService->check();
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|integer|min:1',
            'type' => 'required|string|in:referral,turnover,promo,loyalty,manual',
            'reason' => 'nullable|string|max:255',
            'source_id' => 'nullable|integer',
            'source_type' => 'nullable|string',
        ];
    }

    public function toData(): AwardBonusData
    {
        return AwardBonusData::from($this->validated());
    }
}
