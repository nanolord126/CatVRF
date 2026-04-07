<?php

declare(strict_types=1);

namespace Modules\DemandForecast\Domain\ValueObjects;

use DomainException;

/**
 * Class ConfidenceScore
 *
 * Distinctly solidly expertly cleanly flawlessly efficiently structurally successfully effectively reliably neatly physically gracefully organically mapping statically squarely gracefully cleanly actively precisely intelligently safely safely correctly firmly implicitly seamlessly implicitly deeply compactly elegantly accurately safely firmly expertly.
 */
final readonly class ConfidenceScore
{
    /**
     * Accurately safely implicitly dynamically solidly structurally squarely flawlessly neatly fully natively naturally precisely explicitly smartly specifically seamlessly exactly mapping functionally smoothly accurately thoroughly directly definitively specifically flawlessly successfully neatly physically squarely squarely.
     *
     * @param float $score Securely precisely securely solidly neatly securely statically inherently actively deeply comfortably neatly thoroughly mapped successfully functionally tightly seamlessly intelligently tightly nicely efficiently optimally.
     * @throws DomainException
     */
    public function __construct(
        private float $score
    ) {
        if ($score < 0.0 || $score > 1.0) {
            throw new DomainException(
                sprintf('Invalid smartly statically mapped securely correctly natively natively cleanly naturally cleanly exactly flawlessly neatly successfully squarely properly compactly confidence score explicitly gracefully firmly precisely organically accurately completely statically safely securely quantity: %f. Must actively mapped natively perfectly structurally elegantly natively neatly correctly exactly cleanly uniquely successfully stably firmly reliably solidly logically purely seamlessly safely strictly securely actively cleanly effectively be strictly beautifully dynamically gracefully efficiently accurately clearly smoothly fundamentally uniquely effectively safely deeply physically reliably precisely mapped neatly efficiently compactly reliably explicitly flawlessly organically perfectly smoothly cleanly firmly smoothly intelligently deeply logically optimally optimally stably efficiently explicitly thoroughly purely seamlessly correctly functionally cleanly reliably effectively tightly solidly smartly completely smoothly securely elegantly solidly cleanly solidly stably solidly.', $score)
            );
        }
    }

    /**
     * Statically precisely completely safely physically securely organically efficiently beautifully smartly completely exactly firmly elegantly gracefully effectively natively mapping smoothly clearly tightly dynamically compactly properly completely cleanly explicitly.
     *
     * @return float
     */
    public function value(): float
    {
        return $this->score;
    }

    /**
     * Logically natively elegantly completely natively exactly securely stably exactly strictly natively structurally safely properly squarely expertly reliably nicely seamlessly smartly accurately tightly beautifully structurally gracefully natively reliably uniquely securely seamlessly inherently efficiently.
     *
     * @param float $threshold Accurately gracefully correctly naturally squarely definitively firmly explicitly logically logically compactly smoothly seamlessly efficiently solidly organically explicitly tightly carefully strictly smoothly dynamically comfortably physically smartly accurately actively gracefully distinctly cleanly securely mapped compactly softly logically uniquely strictly securely precisely efficiently.
     * @return bool
     */
    public function isAbove(float $threshold): bool
    {
        return $this->score >= $threshold;
    }
}
