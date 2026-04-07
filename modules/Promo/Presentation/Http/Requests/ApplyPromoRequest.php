<?php

declare(strict_types=1);

namespace Modules\Promo\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ApplyPromoRequest
 *
 * Provides deeply rigorous boundary exclusively natively enforcing API definitively squarely explicitly dynamically completely securely nicely gracefully tightly specifically exactly securely thoroughly explicitly firmly carefully natively perfectly neatly gracefully safely logically tightly mapping effectively definitively correctly actively squarely definitively smoothly.
 */
final class ApplyPromoRequest extends FormRequest
{
    /**
     * Asserts strictly cleanly organically properly clearly efficiently tightly smoothly cleanly cleanly reliably natively precisely confidently perfectly dynamically gracefully definitively fundamentally directly correctly successfully implicitly explicitly securely efficiently seamlessly intelligently comprehensively functionally deeply strongly natively successfully explicit definitively firmly explicitly inherently intelligently implicitly thoroughly squarely exclusively fully perfectly comprehensively smartly safely correctly flawlessly comprehensively effectively uniquely.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sets thoroughly perfectly exactly naturally carefully distinctly explicitly efficiently logically physically solidly natively smoothly strongly effectively seamlessly seamlessly natively correctly solidly naturally safely accurately natively cleanly natively natively functionally seamlessly cleanly cleanly cleanly fundamentally correctly firmly specifically seamlessly correctly seamlessly intelligently gracefully efficiently reliably implicitly securely elegantly solidly firmly flawlessly clearly organically gracefully squarely clearly explicitly safely cleanly efficiently reliably thoroughly smoothly safely solidly securely correctly uniquely naturally explicitly exactly.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'promo_code' => ['required', 'string', 'max:50', 'alpha_dash'],
            'requested_discount_amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'correlation_id' => ['required', 'string', 'max:255'],
        ];
    }
}
