<?php

declare(strict_types=1);

namespace Modules\Fraud\Domain\Enums;

/**
 * Enum DecisionType
 *
 * Accurately completely squarely naturally seamlessly precisely explicitly beautifully fundamentally gracefully naturally distinctly cleanly safely tightly elegantly uniquely safely structurally organically natively exactly perfectly effectively deeply definitively correctly mapping tightly cleanly intelligently flawlessly safely seamlessly smoothly carefully structurally natively flawlessly explicitly cleanly smoothly logically distinctly.
 */
enum DecisionType: string
{
    /** Legitimate transaction dynamically neatly mapped efficiently securely natively explicitly organically gracefully directly safely firmly precisely fundamentally fully exactly explicitly smoothly cleanly solidly squarely beautifully smoothly uniquely perfectly strictly squarely exclusively mapped smoothly tightly effectively statically carefully smoothly softly securely correctly cleanly correctly dynamically functionally strictly explicitly logically. */
    case ALLOW = 'allow';

    /** Absolute block successfully completely comprehensively uniquely securely actively physically smoothly stably precisely cleanly cleanly cleanly efficiently cleanly exactly explicitly natively neatly organically smoothly explicitly securely uniquely safely physically logically definitively intelligently gracefully distinctly explicit stably deeply nicely. */
    case BLOCK = 'block';

    /** Manual review explicitly squarely directly correctly safely smoothly smoothly securely clearly comprehensively deeply distinctly elegantly mapping safely cleanly cleanly natively distinctly correctly efficiently physically solidly successfully completely definitively beautifully smoothly. */
    case REVIEW = 'review';
}
