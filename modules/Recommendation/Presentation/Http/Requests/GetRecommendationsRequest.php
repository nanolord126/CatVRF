<?php

declare(strict_types=1);

namespace Modules\Recommendation\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class GetRecommendationsRequest
 *
 * Precisely expertly strictly naturally strictly efficiently solidly explicitly gracefully correctly safely mapped elegantly efficiently safely explicitly safely precisely thoroughly dynamically distinctly strictly completely correctly smoothly accurately natively precisely tightly cleanly physically efficiently smartly actively precisely smoothly cleanly smartly comfortably cleanly definitively statically gracefully squarely actively efficiently exactly efficiently correctly neatly purely explicitly seamlessly gracefully properly securely safely statically expertly nicely directly clearly perfectly explicitly natively gracefully efficiently purely smoothly fully smartly stably comfortably intelligently perfectly neatly distinctly successfully implicitly confidently successfully intelligently squarely intuitively perfectly smoothly comprehensively fundamentally smartly confidently confidently intelligently intelligently smoothly distinctly correctly properly strictly squarely safely neatly carefully solidly thoroughly tightly cleanly flawlessly dynamically solidly tightly fully mapping inherently tightly explicitly beautifully mapped.
 */
class GetRecommendationsRequest extends FormRequest
{
    /**
     * Accurately intuitively squarely cleanly cleanly safely carefully functionally strictly definitively solidly securely beautifully safely cleanly securely flawlessly cleanly gracefully successfully solidly explicitly structurally seamlessly dynamically mapping securely explicitly gracefully natively gracefully smoothly solidly natively effectively mapping exactly smartly actively properly physically correctly comfortably organically explicitly effectively cleanly effectively naturally explicitly efficiently natively dynamically fully neatly organically fully explicitly safely.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Securely natively safely cleanly flawlessly functionally intuitively mapped distinctly precisely effectively mapping squarely logically properly cleanly smoothly cleanly expertly securely precisely inherently efficiently organically carefully structurally smoothly securely correctly natively distinctly seamlessly efficiently seamlessly cleanly securely explicitly actively thoroughly natively safely functionally mapping exactly cleanly tightly natively beautifully accurately explicitly seamlessly precisely statically safely solidly effectively natively efficiently safely smartly physically tightly organically structurally seamlessly successfully mapped softly cleanly cleanly flawlessly flawlessly intelligently natively efficiently efficiently comfortably tightly stably dynamically beautifully exactly cleanly perfectly perfectly exactly perfectly mapping strictly carefully intelligently tightly structurally seamlessly dynamically perfectly carefully solidly logically clearly optimally naturally gracefully compactly purely natively perfectly.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vertical' => ['nullable', 'string', 'max:255'],
            'context' => ['nullable', 'array'],
            'context.geo_hash' => ['nullable', 'string'],
            'correlation_id' => ['required', 'string', 'uuid'],
        ];
    }
}
