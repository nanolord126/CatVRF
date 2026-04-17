<?php declare(strict_types=1);

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class CreatePropertyBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (class_exists(\App\Services\Fraud\FraudControlService::class) && Auth::check()) {
            $fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass());
            if ($fraudScore > 0.7 && !Auth::user()->hasRole('admin')) {
                \Illuminate\Support\Facades\Log::channel('audit')->warning('Fraud check blocked request', ['class' => __CLASS__, 'score' => $fraudScore]);
                return false;
            }
        }
        return Auth::check() && Auth::user()->tenant_id === tenant()->id;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'integer', 'exists:real_estate_properties,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'viewing_slot' => ['required', 'date_format:Y-m-d H:i:s', 'after:now'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'wallet_id' => ['nullable', 'integer', 'exists:wallets,id'],
            'use_escrow' => ['nullable', 'boolean'],
            'face_id_token' => ['nullable', 'string', 'max:500'],
            'business_group_id' => ['nullable', 'integer', 'exists:business_groups,id'],
            'inn' => ['nullable', 'string', 'max:12', 'min:10'],
            'idempotency_key' => ['nullable', 'string', 'max:255', 'unique:real_estate_bookings,idempotency_key'],
            'correlation_id' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'property_id.required' => 'Property ID is required',
            'property_id.exists' => 'Property not found',
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'User not found',
            'viewing_slot.required' => 'Viewing slot is required',
            'viewing_slot.after' => 'Viewing slot must be in the future',
            'wallet_id.exists' => 'Wallet not found',
            'business_group_id.exists' => 'Business group not found',
            'inn.min' => 'INN must be at least 10 characters',
            'inn.max' => 'INN must not exceed 12 characters',
            'idempotency_key.unique' => 'Booking with this idempotency key already exists',
        ];
    }
}
