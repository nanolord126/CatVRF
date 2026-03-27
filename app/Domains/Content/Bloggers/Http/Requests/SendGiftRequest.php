<?php

declare(strict_types=1);


namespace App\Domains\Content\Bloggers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final /**
 * SendGiftRequest
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SendGiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'integer',
                'min:' . config('bloggers.nft_gifts.min_price_kopiykas'),
                'max:' . config('bloggers.nft_gifts.max_price_kopiykas'),
            ],
            'gift_type' => 'required|string|max:50',
            'message' => 'nullable|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Укажите стоимость подарка',
            'amount.integer' => 'Стоимость должна быть целым числом',
            'amount.min' => 'Минимальная стоимость подарка: ' . (config('bloggers.nft_gifts.min_price_kopiykas') / 100) . ' ₽',
            'amount.max' => 'Максимальная стоимость подарка: ' . (config('bloggers.nft_gifts.max_price_kopiykas') / 100) . ' ₽',
            'gift_type.required' => 'Укажите тип подарка',
        ];
    }
}
