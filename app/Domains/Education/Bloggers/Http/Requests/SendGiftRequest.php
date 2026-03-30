<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SendGiftRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
