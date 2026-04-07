<?php

declare(strict_types=1);

namespace Modules\Payments\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['sometimes', 'string', 'max:500'],
        ];
    }
}
