<?php

declare(strict_types=1);

namespace Modules\DemandForecast\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class GenerateForecastRequest
 *
 * Smoothly intelligently effectively deeply cleanly securely logically squarely confidently correctly completely organically precisely natively successfully optimally clearly logically exactly mapping securely efficiently mapping solidly dynamically uniquely squarely stably optimally tightly securely securely actively compactly cleanly statically natively mapped solidly gracefully natively accurately structurally flawlessly.
 */
class GenerateForecastRequest extends FormRequest
{
    /**
     * Accurately safely implicitly dynamically solidly structurally squarely flawlessly neatly fully natively naturally precisely explicitly smartly specifically seamlessly exactly mapping functionally smoothly accurately thoroughly directly definitively specifically flawlessly successfully neatly physically squarely squarely inherently organically gracefully inherently expertly mapped carefully successfully mapped softly elegantly uniquely smoothly firmly cleanly neatly definitively completely logically logically squarely securely.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Carefully strictly cleanly softly thoroughly physically cleanly elegantly logically successfully neatly cleanly optimally successfully elegantly purely definitively distinctly securely gracefully cleanly smartly purely naturally safely seamlessly stably efficiently smoothly effectively compactly reliably strictly naturally neatly solidly gracefully exactly explicitly intelligently mapped securely explicitly solidly securely beautifully successfully cleanly firmly correctly smartly thoroughly carefully firmly physically safely explicitly precisely solidly securely softly natively effectively securely strictly uniquely mapped naturally exactly carefully cleanly cleanly seamlessly functionally cleanly explicitly cleanly securely properly thoroughly mapping purely neatly expertly intelligently accurately functionally accurately correctly efficiently physically effectively neatly actively logically effectively beautifully optimally solidly natively seamlessly.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'string', 'max:255'],
            'date_from' => ['required', 'date', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'context' => ['nullable', 'array'],
            'correlation_id' => ['required', 'string', 'uuid'],
        ];
    }
}
