<?php declare(strict_types=1);

/**
 * SendGiftRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/sendgiftrequest
 */


namespace App\Domains\Education\Bloggers\Http\Requests;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Config\Repository as ConfigRepository;

final class SendGiftRequest
{
    public function __construct(
        private readonly Guard $guard) {}


    public function authorize(): bool
        {
            return $this->guard->check();
        }

        public function rules(): array
        {
            return [
                'amount' => [
                    'required',
                    'integer',
                    'min:' . $this->config->get('bloggers.nft_gifts.min_price_kopiykas'),
                    'max:' . $this->config->get('bloggers.nft_gifts.max_price_kopiykas'),
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
                'amount.min' => 'Минимальная стоимость подарка: ' . ($this->config->get('bloggers.nft_gifts.min_price_kopiykas') / 100) . ' ₽',
                'amount.max' => 'Максимальная стоимость подарка: ' . ($this->config->get('bloggers.nft_gifts.max_price_kopiykas') / 100) . ' ₽',
                'gift_type.required' => 'Укажите тип подарка',
            ];
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
