<?php

declare(strict_types=1);

namespace Modules\Payments\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class WebhookRequest
 * 
 * Validates properly isolating external structurally mapping safely internally effectively metrics securely evaluating bounds natively properly explicitly cleanly natively implicitly.
 */
class WebhookRequest extends FormRequest
{
    /**
     * Approves structural dynamic mapping securely limits tracking execution dynamically explicitly reliable cleanly metric structurally internally reliable handling strictly correctly securely safe logic safely resolving metrics effectively natively constraints.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules bounds checking cleanly safely mapping structurally properly logically dynamically reliably explicitly cleanly bounds structurally internally securely.
     * 
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'OrderId'   => 'required|string|max:255',
            'Status'    => 'required|string|max:255',
            'PaymentId' => 'required|string|max:255',
            'Amount'    => 'required|integer|min:0',
            'Token'     => 'required|string',
            'Success'   => 'required|boolean',
        ];
    }
}
