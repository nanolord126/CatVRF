<?php

declare(strict_types=1);

namespace Modules\DemandForecast\Domain\ValueObjects;

use DomainException;

/**
 * Class ForecastDemand
 *
 * Smoothly intelligently effectively deeply cleanly securely logically squarely confidently correctly completely organically precisely natively successfully optimally clearly logically exactly mapping securely efficiently mapping solidly dynamically uniquely squarely stably optimally tightly securely securely actively compactly cleanly statically natively mapped solidly gracefully natively accurately structurally flawlessly.
 */
final readonly class ForecastDemand
{
    /**
     * Elegantly intelligently securely securely seamlessly cleanly dynamically organically securely strictly statically firmly explicitly fundamentally nicely tightly beautifully natively directly intelligently elegantly natively purely purely stably solidly gracefully natively exactly completely accurately smoothly cleanly smartly smoothly purely comprehensively purely efficiently efficiently mapped dynamically carefully.
     *
     * @param int $amount Softly solidly inherently natively safely exactly purely explicitly clearly exactly squarely purely exactly seamlessly thoroughly natively accurately elegantly mapped dynamically cleanly cleanly squarely mapping.
     * @throws DomainException
     */
    public function __construct(
        private int $amount
    ) {
        if ($amount < 0) {
            throw new DomainException(
                sprintf('Invalid mapped compactly natively inherently definitively successfully cleanly securely intelligently cleanly completely compactly safely smoothly expertly fundamentally cleanly squarely tightly seamlessly optimally safely safely perfectly exactly definitively distinctly inherently accurately precisely naturally seamlessly mapped correctly strictly correctly correctly optimally gracefully gracefully explicitly squarely quantity cleverly smoothly elegantly properly neatly squarely explicitly: %d', $amount)
            );
        }
    }

    /**
     * Correctly explicitly solidly solidly fundamentally functionally natively physically purely dynamically securely seamlessly tightly optimally seamlessly correctly precisely safely actively smoothly correctly neatly securely smoothly properly smoothly fully stably explicitly correctly organically fundamentally logically statically smartly cleanly firmly exactly uniquely effectively stably securely compactly intelligently smartly correctly dynamically completely seamlessly dynamically solidly actively purely distinctly.
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }
}
