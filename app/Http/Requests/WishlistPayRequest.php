<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\FraudControlService;
use Illuminate\Support\Str;

/**
 * Class WishlistPayRequest
 *
 * Form Request with validation rules for wishlist payment.
 * Validates input before reaching the controller.
 * Includes fraud check integration.
 */
final class WishlistPayRequest extends FormRequest
{
    public function authorize(): bool
    {
        // CANON 2026: Fraud Check in FormRequest
        if ($this->guard->check()) {
            $correlationId = $this->header('X-Correlation-ID') ?? Str::uuid()->toString();
            $fraudResult = app(FraudControlService::class)->check(
                (int) $this->guard->id(),
                'form_request',
                (int) ($this->input('amount', 0)),
                $this->ip(),
                $this->header('X-Device-Fingerprint'),
                $correlationId,
            );
            if ($fraudResult['decision'] === 'block') {
                $this->logger->channel('fraud_alert')->warning('FormRequest blocked', [
                    'class'          => __CLASS__,
                    'correlation_id' => $correlationId,
                    'score'          => $fraudResult['score'],
                ]);

                return false;
            }
        }

        return $this->guard->check();
    }

    public function rules(): array
    {
        return [
            'wishlist_id' => ['required', 'integer', 'exists:wishlists,id'],
            'payment_method' => ['required', 'string', 'in:card,wallet,bonus'],
            'use_bonus' => ['nullable', 'boolean'],
            'amount' => ['nullable', 'integer', 'min:100', 'max:50000000'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'wishlist_id.required' => 'Wishlist ID is required',
            'wishlist_id.integer' => 'Wishlist ID must be an integer',
            'wishlist_id.exists' => 'Wishlist not found',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Payment method must be one of: card, wallet, bonus',
            'use_bonus.boolean' => 'Use bonus must be true or false',
            'amount.integer' => 'Amount must be an integer (in kopecks)',
            'amount.min' => 'Amount must be at least 100 kopecks',
            'amount.max' => 'Amount must not exceed 500,000 RUB',
        ];
    }
}
