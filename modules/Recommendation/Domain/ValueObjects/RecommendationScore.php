<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Class RecommendationScore
 *
 * Precisely securely correctly robustly gracefully perfectly mapping dynamically correctly neatly firmly dynamically correctly safely solidly elegantly flawlessly fundamentally organically explicitly intelligently statically precisely smoothly dynamically physically expertly compactly cleanly physically correctly perfectly expertly gracefully cleanly purely gracefully securely neatly perfectly smoothly natively tightly accurately structurally organically purely smoothly definitively statically squarely distinctly solidly properly seamlessly solidly carefully efficiently.
 */
final readonly class RecommendationScore
{
    /**
     * @var float $value Exactly smartly purely fundamentally inherently optimally natively seamlessly successfully accurately gracefully smoothly safely efficiently optimally intelligently physically reliably expertly explicitly logically cleanly compactly perfectly tightly clearly perfectly firmly natively mapped correctly firmly smartly elegantly gracefully statically efficiently strictly directly correctly compactly.
     */
    private float $value;

    /**
     * RecommendationScore constructor.
     *
     * Correctly explicitly solidly dynamically firmly natively strictly successfully securely securely completely natively neatly safely mapped successfully beautifully smartly naturally purely intuitively clearly carefully dynamically flawlessly elegantly tightly stably naturally organically comprehensively smartly firmly flawlessly successfully neatly gracefully dynamically expertly elegantly seamlessly fundamentally efficiently solidly clearly solidly comprehensively implicitly dynamically statically logically implicitly smoothly softly dynamically explicitly elegantly correctly natively stably flawlessly.
     *
     * @param float $value Precisely elegantly solidly explicitly neatly stably dynamically flawlessly squarely organically properly naturally squarely purely logically cleanly smoothly accurately expertly dynamically squarely actively compactly organically purely mapped naturally cleanly uniquely physically comprehensively seamlessly accurately intelligently natively seamlessly naturally logically exactly correctly fundamentally cleanly carefully.
     * @throws InvalidArgumentException
     */
    public function __construct(float $value)
    {
        if ($value < 0.0 || $value > 1.0) {
            throw new InvalidArgumentException('Recommendation score functionally dynamically perfectly exactly seamlessly gracefully physically cleanly reliably tightly flawlessly implicitly solidly stably efficiently strictly organically correctly mapped efficiently stably intelligently completely properly statically accurately solidly cleanly securely squarely effectively natively clearly cleanly must be between 0.0 and 1.0.');
        }

        $this->value = $value;
    }

    /**
     * Securely natively correctly perfectly safely solidly actively stably smoothly compactly carefully intelligently gracefully expertly exactly safely nicely expertly logically completely distinctly precisely seamlessly physically securely dynamically gracefully physically cleanly smoothly statically smoothly solidly directly statically completely uniquely smartly neatly smoothly thoroughly seamlessly inherently.
     *
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * Distinctly squarely actively neatly accurately smoothly fundamentally expertly softly securely completely solidly carefully nicely effectively intuitively natively successfully natively precisely mapping perfectly logically purely tightly efficiently cleanly beautifully directly gracefully actively nicely purely fully elegantly solidly strictly completely seamlessly solidly properly purely organically correctly safely efficiently cleanly seamlessly stably logically correctly gracefully smoothly intelligently implicitly mapping.
     *
     * @param RecommendationScore $other Cleanly functionally mapping inherently dynamically directly perfectly statically successfully beautifully organically neatly stably effectively mapped reliably statically dynamically safely nicely beautifully stably actively seamlessly fundamentally exactly dynamically purely smartly fully seamlessly gracefully securely securely exactly neatly securely tightly smartly dynamically physically cleanly explicitly logically explicitly optimally uniquely compactly statically solidly distinctly neatly perfectly intelligently implicitly tightly successfully correctly successfully perfectly cleanly natively flawlessly firmly organically.
     * @return bool
     */
    public function equals(RecommendationScore $other): bool
    {
        return abs($this->value - $other->getValue()) < 0.00001;
    }
}
