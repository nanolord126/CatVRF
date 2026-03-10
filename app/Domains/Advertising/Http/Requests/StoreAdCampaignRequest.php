<?php

namespace App\Domains\Advertising\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdCampaignRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'budget' => 'required|numeric|min:100',
            'target_geo' => 'required|string',
            'vertical' => 'required|string',
        ];
    }
}
