<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Http\Requests;

use App\Domains\Advertising\Enums\CampaignType;
use App\Domains\Advertising\Enums\CampaignStatus;
use App\Domains\Advertising\Enums\BudgetType;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateAdCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255|unique:ad_campaigns,name,'.$this->route('advertising'),
            'description' => 'nullable|string|max:1000',
            'campaign_type' => 'sometimes|in:'.implode(',', array_map(fn($e) => $e->value, CampaignType::cases())),
            'status' => 'sometimes|in:'.implode(',', array_map(fn($e) => $e->value, CampaignStatus::cases())),
            'budget' => 'sometimes|numeric|min:0.01|max:999999.99',
            'budget_type' => 'sometimes|in:'.implode(',', array_map(fn($e) => $e->value, BudgetType::cases())),
            'end_date' => 'sometimes|date|after:start_date',
            'target_audience' => 'nullable|string|max:500',
            'performance_data' => 'nullable|json',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Название кампании должно быть уникальным',
            'budget.min' => 'Минимальный бюджет 0.01',
        ];
    }
}
