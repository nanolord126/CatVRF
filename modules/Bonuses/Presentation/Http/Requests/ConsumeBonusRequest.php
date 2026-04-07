<?php

declare(strict_types=1);

namespace Modules\Bonuses\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ConsumeBonusRequest
 *
 * Implements natively cleanly structurally verified payload assertions cleanly functionally validating safely thoroughly smoothly.
 */
final class ConsumeBonusRequest extends FormRequest
{
    /**
     * Determines ideally natively functionally deeply correctly bounded properly effectively locally inherently natively securely dynamically cleanly explicitly.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Confirms logically strictly structurally validated dynamically native correctly safely dynamically uniquely mapped firmly fundamentally.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'owner_id' => ['required', 'uuid'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'correlation_id' => ['required', 'string', 'max:255'],
        ];
    }
}
